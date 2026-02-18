<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WithdrawController extends Controller
{
    public function __construct(private readonly BalanceService $balances)
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $balance = $user->balance ?? null;

        $withdrawals = $this->balances->listUserWithdrawals((int) $user->id, 20);

        /**
         * ✅ Optionnel : empêcher un 2e retrait si un retrait est encore pending/hold
         * Par défaut : FALSE (sinon tu ne pourras jamais faire 2 retraits/jour tant que le 1er n'est pas traité).
         */
        $blockIfPending = (bool) config('valyro.points.block_if_pending', false);

        $hasPendingWithdrawal = false;
        if ($blockIfPending) {
            $hasPendingWithdrawal = Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', Transaction::TYPE_WITHDRAWAL_REQUEST)
                ->whereIn('status', ['pending', 'hold'])
                ->exists();
        }

        // ✅ IMPORTANT : gains OFFRES seulement (aucun retrait ici)
        $earnings = Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', [
                Transaction::TYPE_PENDING_CREDIT,
                Transaction::TYPE_CONFIRMED_CREDIT,
                Transaction::TYPE_PENDING_APPROVED,
                Transaction::TYPE_POSTBACK_REJECTED,
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return view('withdraw', [
            'user' => $user,
            'balance' => $balance,
            'earnings' => $earnings,
            'withdrawals' => $withdrawals,
            'hasPendingWithdrawal' => $hasPendingWithdrawal,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        /**
         * ✅ Optionnel : empêcher un 2e retrait si un retrait est encore pending/hold
         * Par défaut : FALSE (autorise 2 retraits/jour même si le 1er est en cours)
         */
        $blockIfPending = (bool) config('valyro.points.block_if_pending', false);

        if ($blockIfPending) {
            $already = Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', Transaction::TYPE_WITHDRAWAL_REQUEST)
                ->whereIn('status', ['pending', 'hold'])
                ->exists();

            if ($already) {
                return back()
                    ->with('error', 'Tu as déjà un retrait en cours. Attends qu’il soit traité.')
                    ->withInput();
            }
        }

        $data = $request->validate([
            'amount_points'  => ['required', 'integer', 'min:1'],
            'paypal_email'   => ['required', 'email', 'max:190'],
            'device_id'      => ['nullable', 'string', 'max:120'],
            'device_tz'      => ['nullable', 'string', 'max:80'],
            'device_locale'  => ['nullable', 'string', 'max:30'],
        ]);

        $amount = (int) $data['amount_points'];

        // ✅ Appliquer la limite max_single (config)
        $maxSingle = (int) config('valyro.points.max_single', 5000);
        if ($maxSingle > 0 && $amount > $maxSingle) {
            return back()
                ->with('error', 'Montant trop élevé. Maximum par retrait : ' . number_format($maxSingle, 0, ',', ' ') . ' pts.')
                ->withInput();
        }

        $paypalEmail = trim(mb_strtolower((string) $data['paypal_email']));

        // Idempotency: si double submit => même clé = pas de double débit
        $idempotencyKey = (string) ($request->input('idempotency_key') ?: Str::uuid());

        $meta = [
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'device_id' => $data['device_id'] ?? null,
            'device_tz' => $data['device_tz'] ?? null,
            'device_locale' => $data['device_locale'] ?? null,
        ];

        try {
            $this->balances->createPaypalWithdrawal(
                userId: (int) $user->id,
                amountPoints: $amount,
                paypalEmail: $paypalEmail,
                source: 'withdrawal',
                idempotencyKey: $idempotencyKey,
                meta: $meta
            );

            // ✅ Message neutre / sérieux, sans mention de "revue"
            return back()->with('success', 'Demande de retrait envoyée.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Erreur lors de la demande de retrait.')->withInput();
        }
    }
}
