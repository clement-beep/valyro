<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class ApprovePendingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function __invoke(Request $request, BalanceService $balances)
    {
        $userId = (int) ($request->input('user_id') ?? $request->user()?->id);

        // Optionnel : si tu veux valider seulement une partie, tu peux envoyer amount_points
        $amountPoints = $request->filled('amount_points')
            ? (int) $request->input('amount_points')
            : null;

        if ($userId <= 0) {
            return back()->with('error', 'User introuvable (user_id manquant).');
        }

        if ($amountPoints !== null && $amountPoints <= 0) {
            return back()->with('error', 'Montant invalide.');
        }

        try {
            // (Optionnel) vérif user existe => message plus clair si mauvais id
            User::findOrFail($userId);

            $tx = $balances->approvePending(
                userId: $userId,
                amountPoints: $amountPoints, // null => valide tout le pending
                source: 'admin',
                reference: null,
                note: 'Validation du pending (admin)',
                meta: [
                    'admin_user_id' => (int) Auth::id(),
                    'ip' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ],
            );

            $moved = (int) ($tx->amount_cents ?? 0);

            return back()->with(
                'success',
                'Pending validé : +'.number_format($moved / 100, 2, ',', ' ').'€ ajoutés au solde.'
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Erreur lors de la validation du pending.');
        }
    }
}
