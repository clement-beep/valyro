<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PostbackController;
use App\Models\OfferClick;
use App\Models\OfferConversion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostbackSimulatorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function run(Request $request)
    {
        // ✅ Si tu veux tester vite sans remplir : payout_points par défaut = 150
        $data = $request->validate([
            'user_id'       => ['nullable', 'integer', 'min:1'],
            'status'        => ['required', 'in:confirmed,pending,rejected'],
            'payout_points' => ['nullable', 'integer', 'min:0', 'max:500000'],
            'external_id'   => ['nullable', 'string', 'max:120'],
        ]);

        $payoutPoints = (int) ($data['payout_points'] ?? 150);

        /** @var User|null $user */
        $user = null;
        if (!empty($data['user_id'])) {
            $user = User::query()->find((int) $data['user_id']);
        }
        if (!$user) {
            $user = $request->user();
        }

        $token = (string) config('valyro.postback.token', '');
        if (trim($token) === '') {
            return $this->respond($request, false, 422, [
                'message' => 'VALYRO_POSTBACK_TOKEN est vide. Configure-le dans .env puis: php artisan config:clear',
            ]);
        }

        // 1) Crée un click + subid comme en prod
        $subid = 'vly_' . $user->id . '_' . Str::random(16);

        $click = OfferClick::create([
            'user_id'    => $user->id,
            'offer_id'   => null, // offerwall
            'subid'      => $subid,
            'ip'         => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'risk_score' => 0,
            'risk_flags' => [],
            'started_at' => now(),
        ]);

        // 2) external_id (idempotence)
        $externalId = trim((string) ($data['external_id'] ?? ''));
        if ($externalId === '') {
            $externalId = 'sim_' . (string) Str::uuid();
        }

        // 3) Prépare la query EXACTE attendue par PostbackController
        $query = [
            'token'  => $token,
            'subid'  => $subid,
            'status' => $data['status'],
            'payout' => $payoutPoints, // ✅ points directement
            'txnid'  => $externalId,
        ];

        // 4) Appel DIRECT du PostbackController (pas de redirect/brouillard)
        try {
            $server = [
                'REMOTE_ADDR'     => (string) $request->ip(),
                'HTTP_USER_AGENT' => (string) $request->userAgent(),
                'HTTP_HOST'       => (string) $request->getHost(),
                'HTTPS'           => $request->isSecure() ? 'on' : 'off',
            ];

            $pbReq = Request::create('/postback', 'GET', $query, [], [], $server);

            /** @var PostbackController $pb */
            $pb = app(PostbackController::class);
            $resp = $pb->handle($pbReq);

            $http = (int) $resp->getStatusCode();
            $body = (string) $resp->getContent();

            // ✅ Vérif DB : est-ce qu’une conversion a été créée ?
            $conv = OfferConversion::query()
                ->where('external_id', $externalId)
                ->first();

            $balance = DB::table('user_balances')->where('user_id', $user->id)->first();

            $payload = [
                'user_id'        => (int) $user->id,
                'click_id'       => (int) $click->id,
                'subid'          => $subid,
                'external_id'    => $externalId,
                'status'         => (string) $data['status'],
                'payout_points'  => (int) $payoutPoints,
                'postback_http'  => $http,
                'postback_body'  => mb_substr($body, 0, 400),
                'conversion'     => $conv ? [
                    'id'           => (int) $conv->id,
                    'status'       => (string) $conv->status,
                    'network'      => (string) ($conv->network ?? ''),
                    'payout_points'=> (int) ($conv->payout_points ?? 0),
                    'received_at'  => optional($conv->received_at)->toDateTimeString(),
                ] : null,
                'balance'        => $balance ? [
                    'balance_pts' => (int) ($balance->balance_cents ?? 0),
                    'pending_pts' => (int) ($balance->pending_cents ?? 0),
                ] : null,
            ];

            // Si /postback n’a pas répondu 200 => c’est un échec
            if ($http !== 200) {
                $payload['hint'] = 'Le /postback a refusé. Causes fréquentes: token invalide, allowlist IP active, subid invalide, throttle, erreur interne.';
                return $this->respond($request, false, 422, $payload);
            }

            // Si /postback 200 mais aucune conversion en DB => on le considère comme échec “silencieux”
            if (!$conv) {
                $payload['hint'] = 'Le /postback a répondu 200 mais aucune conversion n’a été insérée. Vérifie OfferConversion::$table, migration, et que PostbackController écrit bien dans offer_conversions.';
                return $this->respond($request, false, 422, $payload);
            }

            return $this->respond($request, true, 200, $payload);

        } catch (\Throwable $e) {
            return $this->respond($request, false, 500, [
                'message' => 'Exception simulateur: ' . $e->getMessage(),
            ]);
        }
    }

    private function respond(Request $request, bool $ok, int $status, array $payload)
    {
        // ✅ Si appelé via fetch avec Accept: application/json => JSON
        if ($request->expectsJson()) {
            return response()->json(array_merge(['ok' => $ok], $payload), $status);
        }

        // Fallback HTML (si tu postes depuis un form classique)
        if ($ok) {
            return back()->with('success', '✅ Sim OK — ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
        return back()->with('error', '❌ Sim FAILED — ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
