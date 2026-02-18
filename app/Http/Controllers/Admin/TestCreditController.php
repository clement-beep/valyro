<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Http\Request;

class TestCreditController extends Controller
{
    public function __invoke(Request $request, BalanceService $balances)
    {
        // Valeurs par défaut
        $userId = (int) ($request->input('user_id') ?? $request->user()?->id);
        $amountPoints = (int) ($request->input('amount_cents') ?? $request->input('amount_points') ?? 250);
        $mode = (string) ($request->input('mode') ?? 'pending');

        if ($userId <= 0) {
            return back()->with('error', 'User introuvable (user_id manquant).');
        }

        if ($amountPoints <= 0) {
            return back()->with('error', 'Montant invalide.');
        }

        if (!in_array($mode, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Mode invalide.');
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        if ($mode === 'pending') {
            $balances->creditPending(
                userId: (int) $user->id,
                amountPoints: $amountPoints,
                source: 'admin',
                reference: null,
                note: 'Admin test credit pending',
                meta: [
                    'ip' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ],
            );

            return back()->with(
                'success',
                'Crédit PENDING ajouté (+'.number_format($amountPoints / 100, 2, ',', ' ').'€).'
            );
        }

        $balances->creditConfirmed(
            userId: (int) $user->id,
            amountPoints: $amountPoints,
            source: 'admin',
            reference: null,
            note: 'Admin test credit confirmed',
            meta: [
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ],
        );

        return back()->with(
            'success',
            'Crédit CONFIRMED ajouté (+'.number_format($amountPoints / 100, 2, ',', ' ').'€).'
        );
    }
}
