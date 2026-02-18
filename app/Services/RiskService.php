<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RiskService
{
    /**
     * Anti-fraude retrait (score 0–100).
     * Retour :
     * - score: int
     * - level: low|medium|high
     * - flags: array<string>
     */
    public function evaluateWithdrawal(User $user, array $context = []): array
    {
        // ✅ MODE TEST (via config/valyro.php)
        $testMode   = (bool) config('valyro.risk.test_mode', false);
        $forceLevel = trim((string) config('valyro.risk.force_level', ''));

        if ($testMode && $forceLevel !== '') {
            $forceLevel = strtolower($forceLevel);

            $forcedScore = match ($forceLevel) {
                'high' => 85,
                'medium' => 55,
                'low' => 10,
                default => 0,
            };

            if ($forcedScore > 0) {
                return [
                    'score' => $forcedScore,
                    'level' => in_array($forceLevel, ['low', 'medium', 'high'], true) ? $forceLevel : 'low',
                    'flags' => ["MODE TEST: risk forcé = {$forceLevel}"],
                ];
            }
        }

        $score = 0;
        $flags = [];

        $ip       = trim((string) ($context['ip'] ?? ''));
        $ua       = trim((string) ($context['user_agent'] ?? ''));
        $paypal   = mb_strtolower(trim((string) ($context['paypal_email'] ?? '')));
        $amount   = (int) ($context['amount_points'] ?? 0);
        $deviceId = trim((string) ($context['device_id'] ?? ''));

        // ------------------------------------------------------------
        // A) FACTEURS “DURS” (fortement suspects) => gros points
        // ------------------------------------------------------------

        // 1) PayPal déjà utilisé par un autre compte (ever) => très fort signal
        if ($paypal !== '') {
            $otherUsersSamePaypal = Transaction::query()
                ->where('type', Transaction::TYPE_WITHDRAWAL_REQUEST)
                ->where('meta->paypal_email', $paypal)
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($otherUsersSamePaypal) {
                $score += 45;
                $flags[] = 'PayPal déjà utilisé par un autre compte';
            }
        }

        // 2) Device partagé par un autre compte (90j) => fort signal
        if ($deviceId !== '') {
            $otherUsersSameDevice = Transaction::query()
                ->where('type', Transaction::TYPE_WITHDRAWAL_REQUEST)
                ->where('occurred_at', '>=', now()->subDays(90))
                ->where('user_id', '!=', $user->id)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.device_id')) = ?", [$deviceId])
                ->distinct()
                ->count('user_id');

            if ($otherUsersSameDevice >= 1) {
                $score += 40;
                $flags[] = 'Appareil déjà utilisé par un autre compte (90j)';
            }
        }

        // 3) IP partagée (30j) => signal moyen (VPN / box partagée possible)
        if ($ip !== '') {
            $otherUsersSameIp = Transaction::query()
                ->where('type', Transaction::TYPE_WITHDRAWAL_REQUEST)
                ->where('occurred_at', '>=', now()->subDays(30))
                ->where('meta->ip', $ip)
                ->where('user_id', '!=', $user->id)
                ->distinct()
                ->count('user_id');

            if ($otherUsersSameIp >= 1) {
                $score += 18;
                $flags[] = 'IP déjà utilisée par un autre compte (30j)';
            }
        }

        // 4) User-Agent suspect
        $uaLower = mb_strtolower($ua);
        if ($uaLower !== '' && (
            str_contains($uaLower, 'headless') ||
            str_contains($uaLower, 'phantom') ||
            str_contains($uaLower, 'selenium') ||
            str_contains($uaLower, 'bot')
        )) {
            $score += 25;
            $flags[] = 'Navigateur suspect (bot/headless)';
        }

        // 5) Retrait très proche d’un gain récent (30 min) => léger
        $recentCredit = Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', [Transaction::TYPE_CONFIRMED_CREDIT, Transaction::TYPE_PENDING_APPROVED])
            ->where('occurred_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($recentCredit) {
            $score += 10;
            $flags[] = 'Retrait très proche d’un gain récent (< 30 min)';
        }

        // 6) Montant (petit signal)
        if ($amount >= 5000) { // 50€
            $score += 10;
            $flags[] = 'Montant élevé (≥ 50€)';
        } elseif ($amount >= 3000) { // 30€
            $score += 5;
            $flags[] = 'Montant notable (≥ 30€)';
        }

        // ------------------------------------------------------------
        // B) TRUST SCORE (facteurs de confiance) => réduit le score
        // ------------------------------------------------------------

        $trust = 0;

        // Age compte
        $createdAt = $user->created_at ? Carbon::parse($user->created_at) : null;
        if ($createdAt) {
            $ageDays = $createdAt->diffInDays(now());

            if ($ageDays >= 30) $trust += 20;
            elseif ($ageDays >= 7) $trust += 10;
            elseif ($ageDays < 1) {
                // Compte très récent = petit risque, mais plus faible qu’avant
                $score += 12;
                $flags[] = 'Compte très récent (< 24h)';
            } elseif ($ageDays < 7) {
                $score += 7;
                $flags[] = 'Compte récent (< 7 jours)';
            }
        }

        // Retraits déjà payés dans le passé => très bon signe
        $paidCount = (int) Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', Transaction::TYPE_WITHDRAWAL_REQUEST)
            ->where('status', 'paid')
            ->count();

        if ($paidCount >= 1) {
            $trust += 25;
        }

        // Volume de crédits confirmés (gains validés) => bon signe
        $sumConfirmedCredits = (int) Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', Transaction::TYPE_CONFIRMED_CREDIT)
            ->sum('amount_cents');

        if ($sumConfirmedCredits >= 5000) $trust += 20;        // 50€
        elseif ($sumConfirmedCredits >= 2000) $trust += 10;    // 20€

        // Stabilité device sur 7j (pas trop de devices)
        if ($deviceId !== '') {
            $distinctDevices7d = (int) Transaction::query()
                ->where('type', Transaction::TYPE_WITHDRAWAL_REQUEST)
                ->where('user_id', $user->id)
                ->where('occurred_at', '>=', now()->subDays(7))
                ->whereNotNull('meta->device_id')
                ->count(DB::raw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(meta, '$.device_id'))"));

            if ($distinctDevices7d >= 3) {
                $score += 12;
                $flags[] = 'Changement d’appareil fréquent (7j)';
            } else {
                $trust += 5;
            }
        }

        // ------------------------------------------------------------
        // C) Score final + seuils plus stricts pour HIGH
        // ------------------------------------------------------------
        $score = max(0, min(100, $score - $trust));

        // ✅ HIGH plus rare : il faut de vrais signaux lourds
        $level = 'low';
        if ($score >= 80) $level = 'high';
        elseif ($score >= 45) $level = 'medium';

        // (optionnel) si score high mais aucun signal dur => on redescend
        // évite un high “par accumulation de petits trucs”
        if ($level === 'high') {
            $hasHardFlag = false;
            foreach ($flags as $f) {
                if (
                    str_contains($f, 'PayPal déjà utilisé') ||
                    str_contains($f, 'Appareil déjà utilisé')
                ) {
                    $hasHardFlag = true;
                    break;
                }
            }
            if (!$hasHardFlag) {
                // pas de signal dur => medium
                $level = 'medium';
                $flags[] = 'Sécurité: high abaissé (pas de signal dur)';
            }
        }

        return [
            'score' => (int) $score,
            'level' => $level,
            'flags' => array_values(array_unique($flags)),
        ];
    }
}
