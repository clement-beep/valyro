<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfferConversion;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $since24h = $now->copy()->subDay();
        $since7d  = $now->copy()->subDays(7);

        // --- Users
        $usersTotal = User::query()->count();
        $users7d    = User::query()->where('created_at', '>=', $since7d)->count();

        // --- Balances (global)
        $balances = UserBalance::query()
            ->selectRaw('COALESCE(SUM(balance_cents),0) as sum_balance_cents, COALESCE(SUM(pending_cents),0) as sum_pending_cents')
            ->first();

        $sumBalanceCents = (int) ($balances->sum_balance_cents ?? 0);
        $sumPendingCents = (int) ($balances->sum_pending_cents ?? 0);

        // --- Conversions (OfferConversion)
        $convTotal = OfferConversion::query()->count();
        $conv24h   = OfferConversion::query()->where('received_at', '>=', $since24h)->count();
        $conv7d    = OfferConversion::query()->where('received_at', '>=', $since7d)->count();

        $convByStatus7d = OfferConversion::query()
            ->selectRaw("LOWER(status) as status, COUNT(*) as c")
            ->where('received_at', '>=', $since7d)
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        // payout_points sur conversions (info utile)
        $payout7d = OfferConversion::query()
            ->where('received_at', '>=', $since7d)
            ->selectRaw('COALESCE(SUM(payout_points),0) as s')
            ->value('s');
        $payout7d = (int) $payout7d;

        // --- Transactions (source of truth pour wallet)
        $withdrawReqType = \defined(Transaction::class.'::TYPE_WITHDRAWAL_REQUEST')
            ? Transaction::TYPE_WITHDRAWAL_REQUEST
            : 'withdrawal_request';

        $withdrawDebitType = \defined(Transaction::class.'::TYPE_CONFIRMED_DEBIT')
            ? Transaction::TYPE_CONFIRMED_DEBIT
            : 'confirmed_debit';

        $pendingCreditType = \defined(Transaction::class.'::TYPE_PENDING_CREDIT')
            ? Transaction::TYPE_PENDING_CREDIT
            : 'pending_credit';

        $confirmedCreditType = \defined(Transaction::class.'::TYPE_CONFIRMED_CREDIT')
            ? Transaction::TYPE_CONFIRMED_CREDIT
            : 'confirmed_credit';

        // Retraits demandés (7j)
        $withdrawRequests7d = Transaction::query()
            ->where('type', $withdrawReqType)
            ->where('created_at', '>=', $since7d)
            ->count();

        $withdrawRequestsPending = Transaction::query()
            ->where('type', $withdrawReqType)
            ->where('status', 'pending')
            ->count();

        // Retraits payés (7j) -> confirmed_debit (si tu l’utilises)
        $withdrawPaid7d = Transaction::query()
            ->where('type', $withdrawDebitType)
            ->where('created_at', '>=', $since7d)
            ->count();

        $withdrawPaidSum7d = (int) Transaction::query()
            ->where('type', $withdrawDebitType)
            ->where('created_at', '>=', $since7d)
            ->selectRaw('COALESCE(SUM(amount_cents),0) as s')
            ->value('s');

        // Crédits (7j)
        $creditsPending7d = Transaction::query()
            ->where('type', $pendingCreditType)
            ->where('created_at', '>=', $since7d)
            ->count();

        $creditsConfirmed7d = Transaction::query()
            ->where('type', $confirmedCreditType)
            ->where('created_at', '>=', $since7d)
            ->count();

        $creditsConfirmedSum7d = (int) Transaction::query()
            ->where('type', $confirmedCreditType)
            ->where('created_at', '>=', $since7d)
            ->selectRaw('COALESCE(SUM(amount_cents),0) as s')
            ->value('s');

        // --- Lists (dernier)
        $latestConversions = OfferConversion::query()
            ->with(['user'])
            ->orderByDesc('received_at')
            ->limit(12)
            ->get();

        $latestWithdrawals = Transaction::query()
            ->with(['user'])
            ->where('type', $withdrawReqType)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        return view('admin.dashboard', [
            'usersTotal' => $usersTotal,
            'users7d' => $users7d,

            'sumBalanceCents' => $sumBalanceCents,
            'sumPendingCents' => $sumPendingCents,

            'convTotal' => $convTotal,
            'conv24h' => $conv24h,
            'conv7d' => $conv7d,
            'convByStatus7d' => $convByStatus7d,
            'payout7d' => $payout7d,

            'withdrawRequests7d' => $withdrawRequests7d,
            'withdrawRequestsPending' => $withdrawRequestsPending,
            'withdrawPaid7d' => $withdrawPaid7d,
            'withdrawPaidSum7d' => $withdrawPaidSum7d,

            'creditsPending7d' => $creditsPending7d,
            'creditsConfirmed7d' => $creditsConfirmed7d,
            'creditsConfirmedSum7d' => $creditsConfirmedSum7d,

            'latestConversions' => $latestConversions,
            'latestWithdrawals' => $latestWithdrawals,
        ]);
    }
}
