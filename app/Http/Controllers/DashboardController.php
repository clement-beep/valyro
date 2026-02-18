<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Assure qu'une balance existe
        $balance = $user->balance()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance_cents' => 0, 'pending_cents' => 0]
        );

        // ✅ 1) Gains & validations (uniquement crédits / pending / postback)
        $earnTransactions = $user->transactions()
            ->whereIn('type', [
                'pending_credit',
                'pending_approved',
                'confirmed_credit',
                'postback_rejected',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // ✅ 2) Retraits & échanges (uniquement retraits + refund)
        $withdrawTransactions = $user->transactions()
            ->whereIn('type', [
                'withdrawal_request',
                'withdrawal_refund',
                'confirmed_debit',
                'withdraw',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'balance' => $balance,
            'earnTransactions' => $earnTransactions,
            'withdrawTransactions' => $withdrawTransactions,
        ]);
    }
}
