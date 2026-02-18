<?php

namespace App\Http\Controllers;

use App\Models\OfferClick;
use App\Models\OfferConversion;
use App\Models\Transaction;
use App\Models\UserBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostbackController extends Controller
{
    public function handle(Request $request)
    {
        // Middleware fait déjà le check token/IP/sig si tu l’actives.
        // Ici on fait juste la logique.

        $subid  = trim((string) $request->query('subid', ''));
        $status = strtolower(trim((string) $request->query('status', 'pending')));
        $txnid  = trim((string) $request->query('txnid', $request->query('external_id', '')));
        $network = trim((string) $request->query('network', config('valyro.postback.default_network', 'unknown')));

        // payout peut venir en € (0.50) -> 50 points
        $payoutRaw = (string) $request->query('payout', $request->query('payout_points', '0'));

        if ($subid === '' || $txnid === '') {
            return response('bad_request', 400);
        }

        if (!in_array($status, ['pending', 'confirmed', 'rejected'], true)) {
            // On normalise tout ce qui est “chargeback/cancel/declined” en rejected si tu veux :
            if (in_array($status, ['chargeback','canceled','cancelled','declined','failed'], true)) {
                $status = 'rejected';
            } else {
                $status = 'pending';
            }
        }

        $payoutPts = $this->parsePayoutToPoints($payoutRaw);

        $now = Carbon::now();

        // Meta debug minimal (évite de stocker trop)
        $ip = (string) $request->ip();
        $ua = (string) $request->userAgent();
        $rawPayload = $request->query(); // query params

        try {
            $result = DB::transaction(function () use ($subid, $txnid, $status, $payoutPts, $network, $now, $ip, $ua, $rawPayload) {

                // 1) On doit avoir le click
                /** @var OfferClick|null $click */
                $click = OfferClick::where('subid', $subid)->lockForUpdate()->first();

                if (!$click) {
                    return [
                        'ok' => false,
                        'code' => 404,
                        'message' => 'subid inconnu (aucun click trouvé)',
                        'subid' => $subid,
                        'external_id' => $txnid,
                    ];
                }

                $userId = (int) $click->user_id;

                // 2) On prend/lock le balance
                /** @var UserBalance $balance */
                $balance = UserBalance::where('user_id', $userId)->lockForUpdate()->first();
                if (!$balance) {
                    $balance = UserBalance::create([
                        'user_id' => $userId,
                        'balance_cents' => 0,
                        'pending_cents' => 0,
                    ]);
                    $balance->refresh();
                    $balance = UserBalance::where('user_id', $userId)->lockForUpdate()->first();
                }

                // 3) Conversion idempotente : on cherche d’abord par external_id, sinon par offer_click_id
                /** @var OfferConversion|null $conv */
                $conv = OfferConversion::where('external_id', $txnid)->lockForUpdate()->first();

                if (!$conv) {
                    $conv = OfferConversion::where('offer_click_id', $click->id)->lockForUpdate()->first();
                }

                $isNew = false;

                if (!$conv) {
                    $conv = new OfferConversion();
                    $isNew = true;

                    // Champs minimaux
                    $conv->subid = $subid;
                    $conv->external_id = $txnid;
                    $conv->network = $network;

                    $conv->user_id = $userId;
                    $conv->offer_click_id = $click->id;

                    $conv->received_at = $now;
                    $conv->first_seen_at = $now ?? null; // si colonne existe (sinon ignorée)
                } else {
                    // si conversion existante mais external_id vide, on le fixe
                    if (empty($conv->external_id)) {
                        $conv->external_id = $txnid;
                    }
                    // update “vu”
                    if (empty($conv->received_at)) {
                        $conv->received_at = $now;
                    }
                }

                // Toujours mettre last_seen
                if ($this->hasColumn($conv, 'last_seen_at')) {
                    $conv->last_seen_at = $now;
                }

                // Détails IP/UA/payload si colonnes existent
                if ($this->hasColumn($conv, 'ip')) {
                    $conv->ip = $ip;
                }
                if ($this->hasColumn($conv, 'last_ip')) {
                    $conv->last_ip = $ip;
                }
                if ($this->hasColumn($conv, 'user_agent')) {
                    $conv->user_agent = $ua;
                }
                if ($this->hasColumn($conv, 'raw')) {
                    $conv->raw = $rawPayload;
                }
                if ($this->hasColumn($conv, 'last_payload')) {
                    $conv->last_payload = $rawPayload;
                }

                // 4) Calcul transitions (pending -> confirmed, etc.)
                $prevStatus = strtolower((string) ($conv->status ?? ''));
                $prevPending = (int) ($conv->pending_points ?? 0);
                $prevConfirmed = (int) ($conv->confirmed_points ?? 0);

                // payout principal
                $conv->payout_points = $payoutPts;

                // On remet à plat selon status cible (source de vérité)
                if ($status === 'pending') {
                    $conv->status = 'pending';
                    $conv->pending_points = $payoutPts;
                    $conv->confirmed_points = 0;
                }

                if ($status === 'confirmed') {
                    $conv->status = 'confirmed';
                    $conv->pending_points = 0;
                    $conv->confirmed_points = $payoutPts;
                }

                if ($status === 'rejected') {
                    $conv->status = 'rejected';
                    $conv->pending_points = 0;
                    $conv->confirmed_points = 0;
                }

                // 5) Appliquer delta sur balance + créer transactions idempotentes
                // Règles:
                // - pending : ajoute pending_cents si pas déjà compté
                // - confirmed : si on avait pending, on transfère pending->balance
                // - rejected : si on avait pending, on retire pending (annule)

                $balanceBefore = [
                    'balance' => (int) $balance->balance_cents,
                    'pending' => (int) $balance->pending_cents,
                ];

                // Helper: créer transaction si pas déjà existante
                $createTx = function (array $data) {
                    // idempotence simple via reference unique côté logique
                    $ref = (string) ($data['reference'] ?? '');
                    if ($ref !== '' && Transaction::where('reference', $ref)->exists()) {
                        return null;
                    }
                    return Transaction::create($data);
                };

                // a) pending
                if ($status === 'pending') {
                    // si l’ancienne conversion n’était pas déjà pending avec même montant
                    if (!($prevStatus === 'pending' && $prevPending === $payoutPts)) {
                        // si avant c’était confirmed, on ne “dé-confirme” pas automatiquement (safe)
                        // => on ignore pour éviter abus réseau
                        if ($prevStatus === 'confirmed') {
                            // on ne modifie pas le balance
                        } else {
                            $balance->pending_cents += $payoutPts;

                            $createTx([
                                'user_id' => $userId,
                                'type' => Transaction::TYPE_PENDING_CREDIT,
                                'status' => 'pending',
                                'amount_cents' => $payoutPts,
                                'currency' => 'PTS',
                                'source' => 'postback',
                                'reference' => $txnid,
                                'note' => 'Crédit en attente (postback)',
                                'meta' => [
                                    'subid' => $subid,
                                    'network' => $network,
                                    'conversion_id' => $conv->id ?? null,
                                ],
                                'balance_before_cents' => $balanceBefore['balance'],
                                'balance_after_cents' => $balanceBefore['balance'],
                                'occurred_at' => $now,
                            ]);
                        }
                    }
                }

                // b) confirmed
                if ($status === 'confirmed') {
                    if (!($prevStatus === 'confirmed' && $prevConfirmed === $payoutPts)) {

                        // si on avait pending avant, on transfère
                        if ($prevStatus === 'pending' && $prevPending > 0) {
                            $balance->pending_cents -= $prevPending;
                            $balance->balance_cents += $payoutPts;

                            // Trace “upgrade”
                            $createTx([
                                'user_id' => $userId,
                                'type' => Transaction::TYPE_CONFIRMED_CREDIT,
                                'status' => 'confirmed',
                                'amount_cents' => $payoutPts,
                                'currency' => 'PTS',
                                'source' => 'postback',
                                'reference' => $txnid . '_upgrade',
                                'note' => 'Upgrade pending → confirmé (postback)',
                                'meta' => [
                                    'subid' => $subid,
                                    'network' => $network,
                                    'previous_pending' => $prevPending,
                                ],
                                'balance_before_cents' => $balanceBefore['balance'],
                                'balance_after_cents' => $balanceBefore['balance'] + $payoutPts,
                                'occurred_at' => $now,
                            ]);
                        } else {
                            // sinon on crédite directement en balance
                            $balance->balance_cents += $payoutPts;

                            $createTx([
                                'user_id' => $userId,
                                'type' => Transaction::TYPE_CONFIRMED_CREDIT,
                                'status' => 'confirmed',
                                'amount_cents' => $payoutPts,
                                'currency' => 'PTS',
                                'source' => 'postback',
                                'reference' => $txnid . '_confirmed',
                                'note' => 'Crédit confirmé (postback)',
                                'meta' => [
                                    'subid' => $subid,
                                    'network' => $network,
                                ],
                                'balance_before_cents' => $balanceBefore['balance'],
                                'balance_after_cents' => $balanceBefore['balance'] + $payoutPts,
                                'occurred_at' => $now,
                            ]);
                        }
                    }
                }

                // c) rejected
                if ($status === 'rejected') {
                    // si c’était pending, on annule pending
                    if ($prevStatus === 'pending' && $prevPending > 0) {
                        $balance->pending_cents -= $prevPending;

                        $createTx([
                            'user_id' => $userId,
                            'type' => Transaction::TYPE_POSTBACK_REJECTED,
                            'status' => 'rejected',
                            'amount_cents' => $prevPending,
                            'currency' => 'PTS',
                            'source' => 'postback',
                            'reference' => $txnid . '_rejected',
                            'note' => 'Postback rejeté (annulation pending)',
                            'meta' => [
                                'subid' => $subid,
                                'network' => $network,
                                'previous_pending' => $prevPending,
                            ],
                            'balance_before_cents' => $balanceBefore['balance'],
                            'balance_after_cents' => $balanceBefore['balance'],
                            'occurred_at' => $now,
                        ]);
                    }

                    // si c’était confirmé, on ne débite PAS automatiquement (safe)
                    // si tu veux gérer chargeback plus tard, on fera une route/admin action dédiée
                }

                // sauvegardes
                $balance->save();
                $conv->save();

                return [
                    'ok' => true,
                    'status' => $status,
                    'subid' => $subid,
                    'external_id' => $txnid,
                    'payout_points' => $payoutPts,
                    'postback_http' => 200,
                    'conversion' => [
                        'id' => $conv->id,
                        'status' => $conv->status,
                        'pending_points' => (int) ($conv->pending_points ?? 0),
                        'confirmed_points' => (int) ($conv->confirmed_points ?? 0),
                    ],
                    'balance' => [
                        'balance_pts' => (int) $balance->balance_cents,
                        'pending_pts' => (int) $balance->pending_cents,
                    ],
                    'debug' => [
                        'new' => $isNew,
                        'prev_status' => $prevStatus,
                        'prev_pending' => $prevPending,
                        'prev_confirmed' => $prevConfirmed,
                    ],
                ];
            });

            if (!($result['ok'] ?? false)) {
                return response()->json($result, (int) ($result['code'] ?? 400));
            }

            return response()->json($result);

        } catch (\Throwable $e) {
            Log::error('postback_error', [
                'msg' => $e->getMessage(),
                'subid' => $subid ?? null,
                'txnid' => $txnid ?? null,
            ]);

            return response('error', 500);
        }
    }

    private function parsePayoutToPoints(string $payoutRaw): int
    {
        $payoutRaw = trim($payoutRaw);
        if ($payoutRaw === '') return 0;

        // Si on reçoit déjà des points
        if (preg_match('/^\d+$/', $payoutRaw)) {
            return max(0, (int) $payoutRaw);
        }

        // Sinon, on accepte "0.50" ou "0,50"
        $payoutRaw = str_replace(',', '.', $payoutRaw);
        $float = (float) $payoutRaw;

        // € -> points (1€ = 100 pts)
        $pts = (int) round($float * 100);

        return max(0, $pts);
    }

    private function hasColumn($model, string $column): bool
    {
        try {
            return array_key_exists($column, $model->getAttributes());
        } catch (\Throwable $e) {
            return false;
        }
    }
}
