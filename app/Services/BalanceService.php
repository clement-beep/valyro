<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BalanceService
{
    /**
     * Convention :
     * - On stocke en "cents" mais on l'appelle "points" côté produit.
     * - 1 point = 1 centime.
     */
    public const CURRENCY_EUR = 'EUR';

    // ✅ Policy : si risk HIGH => status HOLD + note admin obligatoire
    private const RISK_NOTE_REQUIRED_LEVEL = 'high';

    // Petit garde-fou : évite de créditer 999999999 par erreur
    private const MAX_ADMIN_CREDIT_POINTS = 500000; // 5 000€ (en points)

    /**
     * ✅ Codes "raison" standardisés (lisibles + stats)
     * On évite de donner des détails qui aideraient à contourner l’anti-fraude.
     */
    public const REASON_GENERIC_REVIEW     = 'GENERIC_REVIEW';
    public const REASON_SECURITY_CHECKS    = 'SECURITY_CHECKS';
    public const REASON_PAYPAL_INVALID     = 'PAYPAL_INVALID';
    public const REASON_DUPLICATE_ACCOUNTS = 'DUPLICATE_ACCOUNTS';
    public const REASON_ABUSE_RULES        = 'ABUSE_RULES';
    public const REASON_REQUEST_ERROR      = 'REQUEST_ERROR';
    public const REASON_KYC_REQUIRED       = 'KYC_REQUIRED';

    public function __construct(private readonly RiskService $riskService)
    {
    }

    /* -------------------------------------------------------------------------
     | ✅ Messages par défaut (pour SELECT côté admin)
     * ---------------------------------------------------------------------- */

    public function defaultRejectMessages(): array
    {
        return [
            self::REASON_SECURITY_CHECKS => [
                'label' => 'Contrôles de sécurité non validés',
                'user_message' => "Votre demande a été refusée après vérifications de sécurité. Si vous pensez qu'il s'agit d'une erreur, contactez le support.",
            ],
            self::REASON_DUPLICATE_ACCOUNTS => [
                'label' => 'Règles anti multi-comptes',
                'user_message' => "Votre demande a été refusée car votre activité ne respecte pas nos règles (multi-comptes / utilisation non autorisée). Contactez le support si vous contestez.",
            ],
            self::REASON_PAYPAL_INVALID => [
                'label' => 'Email PayPal invalide / non vérifiable',
                'user_message' => "Votre demande a été refusée car l’adresse PayPal fournie est invalide ou non vérifiable. Vérifiez votre email PayPal puis réessayez.",
            ],
            self::REASON_REQUEST_ERROR => [
                'label' => 'Erreur / incohérence dans la demande',
                'user_message' => "Votre demande a été refusée car certaines informations sont incohérentes ou incomplètes. Vérifiez les champs puis réessayez.",
            ],
            self::REASON_KYC_REQUIRED => [
                'label' => 'Vérification requise',
                'user_message' => "Votre demande a été refusée : une vérification supplémentaire est requise. Contactez le support pour finaliser le traitement.",
            ],
            self::REASON_GENERIC_REVIEW => [
                'label' => 'Refus (revue manuelle)',
                'user_message' => "Votre demande a été refusée après revue manuelle. Si vous pensez qu'il s'agit d'une erreur, contactez le support.",
            ],
            self::REASON_ABUSE_RULES => [
                'label' => 'Non-respect des règles',
                'user_message' => "Votre demande a été refusée pour non-respect des règles de la plateforme. Contactez le support si vous contestez.",
            ],
        ];
    }

    public function defaultPaidMessages(): array
    {
        return [
            'PAID_SENT' => [
                'label' => 'PayPal envoyé',
                'user_message' => "Paiement envoyé. Vous devriez le recevoir sur votre compte PayPal sous peu.",
            ],
            'PAID_PROCESSING' => [
                'label' => 'En cours de traitement',
                'user_message' => "Paiement validé et en cours de traitement. Merci pour votre patience.",
            ],
        ];
    }

    private function resolveDefaultRejectUserMessage(?string $reasonCode): string
    {
        $reasonCode = strtoupper(trim((string) $reasonCode));
        $map = $this->defaultRejectMessages();

        if ($reasonCode !== '' && isset($map[$reasonCode]['user_message'])) {
            return (string) $map[$reasonCode]['user_message'];
        }

        return "Votre demande a été refusée après vérification. Si vous pensez qu'il s'agit d'une erreur, contactez le support.";
    }

    private function resolveDefaultPaidUserMessage(?string $code): string
    {
        $code = strtoupper(trim((string) $code));
        $map = $this->defaultPaidMessages();

        if ($code !== '' && isset($map[$code]['user_message'])) {
            return (string) $map[$code]['user_message'];
        }

        return "Paiement envoyé. Vous devriez le recevoir sur votre compte PayPal sous peu.";
    }

    /* -------------------------------------------------------------------------
     | ADMIN (tests rapides)
     * ---------------------------------------------------------------------- */

    public function adminCredit(
        int $userId,
        int $amountPoints,
        ?string $note = null,
        array $meta = [],
        ?Carbon $occurredAt = null
    ): Transaction {
        $this->assertPositiveAmount($amountPoints);
        $this->assertAdminCreditLimit($amountPoints);

        return $this->creditConfirmed(
            userId: $userId,
            amountPoints: $amountPoints,
            source: 'admin',
            reference: 'adm_cc_' . Str::uuid(),
            note: $note ?: 'Crédit admin (test)',
            meta: array_merge([
                'admin_credit' => true,
                'reason' => 'test',
            ], $meta),
            occurredAt: $occurredAt
        );
    }

    public function adminCreditPending(
        int $userId,
        int $amountPoints,
        ?string $note = null,
        array $meta = [],
        ?Carbon $occurredAt = null
    ): Transaction {
        $this->assertPositiveAmount($amountPoints);
        $this->assertAdminCreditLimit($amountPoints);

        return $this->creditPending(
            userId: $userId,
            amountPoints: $amountPoints,
            source: 'admin',
            reference: 'adm_pc_' . Str::uuid(),
            note: $note ?: 'Crédit admin en attente (test)',
            meta: array_merge([
                'admin_credit' => true,
                'reason' => 'test',
            ], $meta),
            occurredAt: $occurredAt
        );
    }

    /* -------------------------------------------------------------------------
     | Credits
     * ---------------------------------------------------------------------- */

    public function creditPending(
        int $userId,
        int $amountPoints,
        string $source = 'system',
        ?string $reference = null,
        ?string $note = null,
        array $meta = [],
        ?Carbon $occurredAt = null
    ): Transaction {
        $this->assertPositiveAmount($amountPoints);

        return DB::transaction(function () use ($userId, $amountPoints, $source, $reference, $note, $meta, $occurredAt) {
            $balance = $this->getOrCreateBalanceForUpdate($userId);

            $beforePending   = (int) $balance->pending_cents;
            $beforeConfirmed = (int) $balance->balance_cents;

            $balance->pending_cents = $beforePending + $amountPoints;
            $balance->save();

            $occurredAt = $occurredAt ?: now();

            return Transaction::create([
                'user_id' => $userId,
                'type' => 'pending_credit',
                'amount_cents' => $amountPoints,
                'currency' => self::CURRENCY_EUR,
                'status' => 'pending',
                'source' => $source,
                'reference' => $reference ?? ('pc_' . Str::uuid()),
                'note' => $note,
                'balance_before_cents' => $beforeConfirmed,
                'balance_after_cents'  => $beforeConfirmed,
                'occurred_at' => $occurredAt,
                'meta' => array_merge([
                    'pending_before_cents' => $beforePending,
                    'pending_after_cents'  => (int) $balance->pending_cents,
                ], $meta),
            ]);
        });
    }

    public function creditConfirmed(
        int $userId,
        int $amountPoints,
        string $source = 'system',
        ?string $reference = null,
        ?string $note = null,
        array $meta = [],
        ?Carbon $occurredAt = null
    ): Transaction {
        $this->assertPositiveAmount($amountPoints);

        return DB::transaction(function () use ($userId, $amountPoints, $source, $reference, $note, $meta, $occurredAt) {
            $balance = $this->getOrCreateBalanceForUpdate($userId);

            $beforeConfirmed = (int) $balance->balance_cents;

            $balance->balance_cents = $beforeConfirmed + $amountPoints;
            $balance->save();

            $occurredAt = $occurredAt ?: now();

            return Transaction::create([
                'user_id' => $userId,
                'type' => 'confirmed_credit',
                'amount_cents' => $amountPoints,
                'currency' => self::CURRENCY_EUR,
                'status' => 'confirmed',
                'source' => $source,
                'reference' => $reference ?? ('cc_' . Str::uuid()),
                'note' => $note,
                'balance_before_cents' => $beforeConfirmed,
                'balance_after_cents'  => (int) $balance->balance_cents,
                'occurred_at' => $occurredAt,
                'meta' => $meta,
            ]);
        });
    }

    public function approvePending(
        int $userId,
        ?int $amountPoints = null,
        string $source = 'admin',
        ?string $reference = null,
        ?string $note = null,
        array $meta = [],
        ?Carbon $occurredAt = null
    ): Transaction {
        return DB::transaction(function () use ($userId, $amountPoints, $source, $reference, $note, $meta, $occurredAt) {

            $balance = $this->getOrCreateBalanceForUpdate($userId);

            $pending = (int) $balance->pending_cents;
            if ($pending <= 0) {
                throw new RuntimeException('Aucun solde en attente à valider.');
            }

            $move = $amountPoints === null ? $pending : (int) $amountPoints;
            $this->assertPositiveAmount($move);

            if ($move > $pending) {
                throw new RuntimeException('Montant à valider supérieur au solde en attente.');
            }

            $beforeConfirmed = (int) $balance->balance_cents;
            $beforePending   = $pending;

            $balance->pending_cents = $beforePending - $move;
            $balance->balance_cents = $beforeConfirmed + $move;
            $balance->save();

            $occurredAt = $occurredAt ?: now();

            return Transaction::create([
                'user_id' => $userId,
                'type' => 'pending_approved',
                'amount_cents' => $move,
                'currency' => self::CURRENCY_EUR,
                'status' => 'confirmed',
                'source' => $source,
                'reference' => $reference ?? ('pa_' . Str::uuid()),
                'note' => $note ?? 'Validation du solde en attente',
                'balance_before_cents' => $beforeConfirmed,
                'balance_after_cents'  => (int) $balance->balance_cents,
                'occurred_at' => $occurredAt,
                'meta' => array_merge([
                    'pending_before_cents' => $beforePending,
                    'pending_after_cents'  => (int) $balance->pending_cents,
                ], $meta),
            ]);
        });
    }

    /* -------------------------------------------------------------------------
     | Withdrawals (Points)
     * ---------------------------------------------------------------------- */

    public function createPaypalWithdrawal(
        int $userId,
        int $amountPoints,
        string $paypalEmail,
        string $source = 'withdrawal',
        ?string $idempotencyKey = null,
        array $meta = []
    ): Transaction {
        $this->assertPositiveAmount($amountPoints);

        $minPts = $this->cfgMinWithdraw();
        if ($amountPoints < $minPts) {
            throw new RuntimeException('Minimum : ' . number_format($minPts, 0, ',', ' ') . ' pts.');
        }

        $paypalEmail = trim(mb_strtolower($paypalEmail));
        if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email PayPal invalide.');
        }

        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();

        return DB::transaction(function () use ($userId, $amountPoints, $paypalEmail, $source, $idempotencyKey, $meta) {

            $existing = Transaction::query()
                ->where('user_id', $userId)
                ->where('type', 'withdrawal_request')
                ->where('meta->idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $this->hydrateWithdrawalTx($existing);
            }

            $this->assertWithdrawalRules($userId, $amountPoints);

            $balance = $this->getOrCreateBalanceForUpdate($userId);
            $beforeConfirmed = (int) $balance->balance_cents;

            if ($amountPoints > $beforeConfirmed) {
                throw new RuntimeException('Solde insuffisant.');
            }

            $balance->balance_cents = $beforeConfirmed - $amountPoints;
            $balance->save();

            $reference = 'wd_' . Str::uuid();

            /** @var User|null $userModel */
            $userModel = User::find($userId);

            $risk = $userModel
                ? $this->riskService->evaluateWithdrawal($userModel, [
                    'ip' => (string) ($meta['ip'] ?? ''),
                    'user_agent' => (string) ($meta['user_agent'] ?? ''),
                    'paypal_email' => $paypalEmail,
                    'amount_points' => $amountPoints,
                    'device_id' => (string) ($meta['device_id'] ?? ''),
                ])
                : ['score' => 0, 'level' => 'low', 'flags' => []];

            $risk = $this->mergeOfferClicksRisk($risk, $userId);
            $risk = $this->applyRiskTestOverride($risk);

            $riskLevel = (string) ($risk['level'] ?? 'low');
            if ($riskLevel === '') $riskLevel = 'low';

            $isHigh = ($riskLevel === self::RISK_NOTE_REQUIRED_LEVEL);
            $status = $isHigh ? 'hold' : 'pending';

            $tx = Transaction::create([
                'user_id' => $userId,
                'type' => 'withdrawal_request',
                'amount_cents' => $amountPoints,
                'currency' => self::CURRENCY_EUR,
                'status' => $status,
                'source' => $source,
                'reference' => $reference,
                'note' => 'Demande d’échange PayPal',
                'balance_before_cents' => $beforeConfirmed,
                'balance_after_cents'  => (int) $balance->balance_cents,
                'occurred_at' => now(),
                'meta' => array_merge([
                    'method' => 'paypal',
                    'paypal_email' => $paypalEmail,
                    'idempotency_key' => $idempotencyKey,
                    'points' => $amountPoints,
                    'approx_eur' => round($amountPoints / 100, 2),

                    'ip' => (string) ($meta['ip'] ?? ''),
                    'user_agent' => (string) ($meta['user_agent'] ?? ''),
                    'device_id' => (string) ($meta['device_id'] ?? ''),
                    'device_tz' => (string) ($meta['device_tz'] ?? ''),
                    'device_locale' => (string) ($meta['device_locale'] ?? ''),

                    'risk_score' => (int) ($risk['score'] ?? 0),
                    'risk_level' => $riskLevel,
                    'risk_flags' => (array) ($risk['flags'] ?? []),

                    'needs_admin_note' => $isHigh,

                    'is_hold' => $isHigh,
                    'hold_reason' => $isHigh ? 'risk_high' : null,
                    'hold_at' => $isHigh ? now()->toIso8601String() : null,
                ], $meta),
            ]);

            return $this->hydrateWithdrawalTx($tx);
        });
    }

    private function mergeOfferClicksRisk(array $risk, int $userId): array
    {
        try {
            $now = now();

            $maxRisk7d = (int) DB::table('offer_clicks')
                ->where('user_id', $userId)
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->max('risk_score');

            $burst1m = (int) DB::table('offer_clicks')
                ->where('user_id', $userId)
                ->where('created_at', '>=', $now->copy()->subMinute())
                ->count();

            $flags = (array) ($risk['flags'] ?? []);
            $score = (int) ($risk['score'] ?? 0);

            if ($maxRisk7d >= 60) {
                $score += 25;
                $flags[] = 'OfferClicks: max_risk_7d>=60';
            } elseif ($maxRisk7d >= 40) {
                $score += 10;
                $flags[] = 'OfferClicks: max_risk_7d>=40';
            }

            if ($burst1m >= 10) {
                $score += 30;
                $flags[] = 'OfferClicks: burst_clicks_1m>=10';
            } elseif ($burst1m >= 6) {
                $score += 15;
                $flags[] = 'OfferClicks: burst_clicks_1m>=6';
            }

            $level = (string) ($risk['level'] ?? 'low');
            $level = $level !== '' ? $level : 'low';

            if ($level !== 'high') {
                if ($score >= 80) $level = 'high';
                elseif ($score >= 45) $level = 'medium';
                else $level = 'low';
            }

            $risk['score'] = $score;
            $risk['level'] = $level;
            $risk['flags'] = array_values(array_unique($flags));

            return $risk;
        } catch (\Throwable $e) {
            return $risk;
        }
    }

    private function applyRiskTestOverride(array $risk): array
    {
        $testMode = (bool) config('valyro.risk.test_mode', false);
        $forced   = (string) config('valyro.risk.force_level', '');

        if (!$testMode) return $risk;

        $forced = trim(strtolower($forced));
        if (!in_array($forced, ['low', 'medium', 'high'], true)) {
            return $risk;
        }

        $risk['level'] = $forced;
        $risk['score'] = $forced === 'high' ? 90 : ($forced === 'medium' ? 50 : 10);

        $flags = (array) ($risk['flags'] ?? []);
        $flags[] = 'MODE TEST: risk forcé = ' . $forced;
        $risk['flags'] = array_values(array_unique($flags));

        return $risk;
    }

    public function releaseWithdrawalHold(Transaction $withdrawalTx, int $adminUserId, string $adminNote): Transaction
    {
        $this->assertTxType($withdrawalTx, 'withdrawal_request');

        return DB::transaction(function () use ($withdrawalTx, $adminUserId, $adminNote) {

            $tx = Transaction::query()
                ->where('id', $withdrawalTx->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($tx->status ?? null) !== 'hold') {
                throw new RuntimeException('Ce retrait n’est pas en revue.');
            }

            $note = trim($adminNote);
            if ($note === '') {
                throw new RuntimeException('Note admin obligatoire pour libérer un retrait en revue.');
            }

            $meta = $this->metaToArray($tx->meta);
            $meta['admin_action']  = 'release_hold';
            $meta['admin_user_id'] = $adminUserId;
            $meta['admin_note']    = $note;
            $meta['released_at']   = now()->toIso8601String();
            $meta['is_hold']       = false;

            $tx->status = 'pending';
            $tx->meta   = $meta;
            $tx->save();

            return $this->hydrateWithdrawalTx($tx);
        });
    }

    public function markWithdrawalPaid(
        Transaction $withdrawalTx,
        int $adminUserId,
        ?string $adminNote = null,
        ?string $reasonCode = null,
        ?string $userMessage = null
    ): Transaction {
        $this->assertTxType($withdrawalTx, 'withdrawal_request');

        return DB::transaction(function () use ($withdrawalTx, $adminUserId, $adminNote, $reasonCode, $userMessage) {

            $tx = Transaction::query()
                ->where('id', $withdrawalTx->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current = (string) ($tx->status ?? 'pending');

            if ($current !== 'pending') {
                throw new RuntimeException('Action impossible : statut = ' . $this->labelStatusFr($current) . '.');
            }

            $meta = $this->metaToArray($tx->meta);

            if (!empty($meta['needs_admin_note']) && trim((string) $adminNote) === '') {
                throw new RuntimeException('Note admin obligatoire pour un retrait à risque élevé.');
            }

            $meta['admin_action']  = 'paid';
            $meta['admin_user_id'] = $adminUserId;
            $meta['admin_note']    = trim((string) $adminNote) !== '' ? trim((string) $adminNote) : 'PayPal envoyé';
            $meta['paid_at']       = now()->toIso8601String();

            $meta['reason_code'] = $reasonCode ? strtoupper(trim((string) $reasonCode)) : 'PAID_SENT';

            // ✅ Option: exposer la note admin à l’utilisateur si userMessage vide
            $exposeAdminNoteToUser = (bool) config('valyro.admin.expose_admin_note_to_user', false);

            $meta['user_message'] =
                trim((string) $userMessage) !== '' ? trim((string) $userMessage)
                : (
                    $exposeAdminNoteToUser && trim((string) $adminNote) !== ''
                        ? trim((string) $adminNote)
                        : $this->resolveDefaultPaidUserMessage($meta['reason_code'])
                );

            $tx->status = 'paid';
            $tx->meta   = $meta;
            $tx->save();

            return $this->hydrateWithdrawalTx($tx);
        });
    }

    public function rejectWithdrawal(
        Transaction $withdrawalTx,
        int $adminUserId,
        ?string $adminNote = null,
        ?string $reasonCode = null,
        ?string $userMessage = null
    ): Transaction {
        $this->assertTxType($withdrawalTx, 'withdrawal_request');

        return DB::transaction(function () use ($withdrawalTx, $adminUserId, $adminNote, $reasonCode, $userMessage) {

            $tx = Transaction::query()
                ->where('id', $withdrawalTx->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current = (string) ($tx->status ?? 'pending');

            if (!in_array($current, ['pending', 'hold'], true)) {
                throw new RuntimeException('Action impossible : statut = ' . $this->labelStatusFr($current) . '.');
            }

            $meta = $this->metaToArray($tx->meta);

            if (!empty($meta['needs_admin_note']) && trim((string) $adminNote) === '') {
                throw new RuntimeException('Note admin obligatoire pour un retrait à risque élevé.');
            }

            $amountPoints = (int) $tx->amount_cents;
            $userId       = (int) $tx->user_id;

            $balance = $this->getOrCreateBalanceForUpdate($userId);
            $beforeConfirmed = (int) $balance->balance_cents;

            $balance->balance_cents = $beforeConfirmed + $amountPoints;
            $balance->save();

            $meta['admin_action']  = 'rejected';
            $meta['admin_user_id'] = $adminUserId;
            $meta['admin_note']    = trim((string) $adminNote) !== '' ? trim((string) $adminNote) : 'Rejet admin';
            $meta['rejected_at']   = now()->toIso8601String();

            $meta['reason_code'] = $reasonCode ? strtoupper(trim((string) $reasonCode)) : self::REASON_GENERIC_REVIEW;

            // ✅ Option: exposer la note admin à l’utilisateur si userMessage vide
            $exposeAdminNoteToUser = (bool) config('valyro.admin.expose_admin_note_to_user', false);

            $meta['user_message'] =
                trim((string) $userMessage) !== '' ? trim((string) $userMessage)
                : (
                    $exposeAdminNoteToUser && trim((string) $adminNote) !== ''
                        ? trim((string) $adminNote)
                        : $this->resolveDefaultRejectUserMessage($meta['reason_code'])
                );

            $tx->status = 'rejected';
            $tx->meta   = $meta;
            $tx->save();

            Transaction::create([
                'user_id' => $userId,
                'type' => 'withdrawal_refund',
                'amount_cents' => $amountPoints,
                'currency' => self::CURRENCY_EUR,
                'status' => 'confirmed',
                'source' => 'admin',
                'reference' => 'wdrfd_' . Str::uuid(),
                'note' => 'Échange rejeté : points recrédités',
                'balance_before_cents' => $beforeConfirmed,
                'balance_after_cents'  => (int) $balance->balance_cents,
                'occurred_at' => now(),
                'meta' => [
                    'withdrawal_reference' => $tx->reference,
                    'admin_user_id' => $adminUserId,
                ],
            ]);

            return $this->hydrateWithdrawalTx($tx);
        });
    }

    /* -------------------------------------------------------------------------
     | Lists
     * ---------------------------------------------------------------------- */

    public function listUserWithdrawals(int $userId, int $limit = 20)
    {
        $items = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'withdrawal_request')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $items->map(fn (Transaction $t) => $this->hydrateWithdrawalTx($t));
    }

    public function listActionableWithdrawals(int $limit = 200)
    {
        $items = Transaction::query()
            ->with('user')
            ->where('type', 'withdrawal_request')
            ->whereIn('status', ['pending', 'hold'])
            ->orderByRaw("FIELD(status,'hold','pending')")
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return $items->map(fn (Transaction $t) => $this->hydrateWithdrawalTx($t));
    }

    public function listRecentWithdrawals(int $limit = 50)
    {
        $items = Transaction::query()
            ->with('user')
            ->where('type', 'withdrawal_request')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $items->map(fn (Transaction $t) => $this->hydrateWithdrawalTx($t));
    }

    /* -------------------------------------------------------------------------
     | Helpers
     * ---------------------------------------------------------------------- */

    private function cfgMinWithdraw(): int { return (int) config('valyro.points.min_withdraw', 1000); }
    private function cfgMaxCountDay(): int { return (int) config('valyro.points.max_count_day', 2); }
    private function cfgMaxPerDayPts(): int { return (int) config('valyro.points.max_per_day', 5000); }
    private function cfgCooldownHours(): int { return (int) config('valyro.points.cooldown_h', 24); }

    private function getOrCreateBalanceForUpdate(int $userId): UserBalance
    {
        $balance = UserBalance::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            UserBalance::create([
                'user_id' => $userId,
                'balance_cents' => 0,
                'pending_cents' => 0,
            ]);

            $balance = UserBalance::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return $balance;
    }

    /**
     * ✅ Rules withdrawals
     *
     * Fix:
     * - “Aujourd’hui” = jour calendaire Europe/Paris (borne locale, query UTC).
     * - Le message de cooldown doit refléter la fenêtre du cooldown (ex: 24h),
     *   donc il doit pouvoir afficher 2 si 2 retraits dans les dernières 24h.
     */
    private function assertWithdrawalRules(int $userId, int $amountPoints): void
    {
        $tz = (string) config('valyro.timezone', config('app.timezone', 'Europe/Paris'));


        // Now local
        $nowLocal = now()->timezone($tz);

        // Bornes journée locale -> UTC pour DB
        $startUtc = $nowLocal->copy()->startOfDay()->utc();
        $endUtc   = $nowLocal->copy()->endOfDay()->utc();

        $cooldownSeconds = max(0, $this->cfgCooldownHours() * 3600);
        $maxPerDayCount  = $this->cfgMaxCountDay();
        $maxPerDayPts    = $this->cfgMaxPerDayPts();

        // 1) Limite de nombre / jour (calendaire)
        $countToday = (int) Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'withdrawal_request')
            ->whereBetween(DB::raw('COALESCE(occurred_at, created_at)'), [$startUtc, $endUtc])
            ->lockForUpdate()
            ->count();

        if ($countToday >= $maxPerDayCount) {
            throw new RuntimeException('Limite atteinte : maximum ' . $maxPerDayCount . ' retraits par jour.');
        }

        // 2) Cooldown (fenêtre glissante)
        if ($cooldownSeconds > 0) {
            $cooldownStartLocal = $nowLocal->copy()->subSeconds($cooldownSeconds);
            $cooldownStartUtc   = $cooldownStartLocal->copy()->utc();

            // ✅ Compteur dans la fenêtre du cooldown (ex: dernières 24h)
            $countInCooldownWindow = (int) Transaction::query()
                ->where('user_id', $userId)
                ->where('type', 'withdrawal_request')
                ->whereBetween(DB::raw('COALESCE(occurred_at, created_at)'), [$cooldownStartUtc, $endUtc])
                ->lockForUpdate()
                ->count();

            // Dernière demande
            $last = Transaction::query()
                ->where('user_id', $userId)
                ->where('type', 'withdrawal_request')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($last) {
                $lastAt = $last->occurred_at ?? $last->created_at;

                if ($lastAt) {
                    $lastLocal = Carbon::parse($lastAt)->timezone($tz);
                    $diffSeconds = $lastLocal->diffInSeconds($nowLocal, false);

                    if ($diffSeconds >= 0 && $diffSeconds < $cooldownSeconds) {
                        $remaining = $cooldownSeconds - $diffSeconds;
                        $h = (int) floor($remaining / 3600);
                        $m = (int) floor(($remaining % 3600) / 60);

                        // ✅ Message basé sur la fenêtre cooldown
                        $label = $this->cfgCooldownHours() >= 24
                            ? 'sur les dernières 24h'
                            : 'sur la dernière période';

                        throw new RuntimeException(
                            "Tu as déjà effectué {$countInCooldownWindow} retrait(s) {$label}. Cooldown actif : réessaie dans {$h}h {$m}m."
                        );
                    }
                }
            }
        }

        // 3) Limite montant / jour
        $sumToday = (int) Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'withdrawal_request')
            ->whereBetween(DB::raw('COALESCE(occurred_at, created_at)'), [$startUtc, $endUtc])
            ->lockForUpdate()
            ->sum('amount_cents');

        if (($sumToday + $amountPoints) > $maxPerDayPts) {
            $left = max(0, $maxPerDayPts - $sumToday);

            throw new RuntimeException(
                'Limite montant/jour : ' . number_format($maxPerDayPts, 0, ',', ' ') .
                ' pts. Il te reste ' . number_format($left, 0, ',', ' ') . ' pts aujourd’hui.'
            );
        }
    }

    private function hydrateWithdrawalTx(Transaction $t): Transaction
    {
        $meta = $this->metaToArray($t->meta);

        $t->setAttribute('paypal_email', $meta['paypal_email'] ?? ($meta['paypalEmail'] ?? null));
        $t->setAttribute('method', $meta['method'] ?? 'paypal');
        $t->setAttribute('requested_at', $t->occurred_at ?? $t->created_at);

        $t->setAttribute('risk_score', (int) ($meta['risk_score'] ?? 0));
        $t->setAttribute('risk_level', (string) ($meta['risk_level'] ?? 'low'));
        $t->setAttribute('risk_flags', (array) ($meta['risk_flags'] ?? []));

        $t->setAttribute('needs_admin_note', (bool) ($meta['needs_admin_note'] ?? false));

        $t->setAttribute('ip', $meta['ip'] ?? null);
        $t->setAttribute('user_agent', $meta['user_agent'] ?? null);
        $t->setAttribute('idempotency_key', $meta['idempotency_key'] ?? null);

        $t->setAttribute('device_id', $meta['device_id'] ?? null);
        $t->setAttribute('device_tz', $meta['device_tz'] ?? null);
        $t->setAttribute('device_locale', $meta['device_locale'] ?? null);

        // ✅ Exposé user (safe)
        $t->setAttribute('reason_code', $meta['reason_code'] ?? null);
        $t->setAttribute('user_message', $meta['user_message'] ?? null);

        return $t;
    }

    private function assertPositiveAmount(int $amountPoints): void
    {
        if ($amountPoints <= 0) {
            throw new RuntimeException('Montant invalide.');
        }
    }

    private function assertAdminCreditLimit(int $amountPoints): void
    {
        if ($amountPoints > self::MAX_ADMIN_CREDIT_POINTS) {
            throw new RuntimeException(
                'Montant trop élevé pour un crédit admin. Max = ' .
                number_format(self::MAX_ADMIN_CREDIT_POINTS, 0, ',', ' ') . ' pts.'
            );
        }
    }

    private function metaToArray($meta): array
    {
        if (is_array($meta)) return $meta;
        if (is_object($meta)) return (array) $meta;

        if (is_string($meta) && $meta !== '') {
            $decoded = json_decode($meta, true);
            if (is_array($decoded)) return $decoded;
        }

        return [];
    }

    private function labelStatusFr(string $status): string
    {
        return match ($status) {
            'paid' => 'Payé',
            'rejected' => 'Rejeté',
            'hold' => 'En revue',
            default => 'En attente',
        };
    }

    private function assertTxType(Transaction $tx, string $expectedType): void
    {
        if (($tx->type ?? null) !== $expectedType) {
            throw new InvalidArgumentException('Transaction invalide.');
        }
    }
}
