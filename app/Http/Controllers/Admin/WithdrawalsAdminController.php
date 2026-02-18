<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class WithdrawalsAdminController extends Controller
{
    public function __construct(private readonly BalanceService $balanceService)
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Admin - page de gestion des retraits PayPal
     * Vue attend : $pending et $recent
     */
    public function index(Request $request)
    {
        $pendingLimit = (int) $request->integer('pending_limit', 200);
        $recentLimit  = (int) $request->integer('recent_limit', 200);

        $pendingLimit = max(1, min($pendingLimit, 1000));
        $recentLimit  = max(1, min($recentLimit, 2000));

        $pending = $this->balanceService->listActionableWithdrawals($pendingLimit);
        $recent  = $this->balanceService->listRecentWithdrawals($recentLimit);

        return view('admin.withdrawals', [
            'pending' => $pending,
            'recent'  => $recent,
        ]);
    }

    /**
     * Admin - lever un HOLD (note obligatoire)
     */
    public function release(Request $request, Transaction $transaction)
    {
        try {
            $this->assertIsWithdrawalRequest($transaction);

            $note = trim((string) $request->input('admin_note', ''));

            if ($note === '') {
                return back()->with('error', 'Note admin obligatoire pour libérer un retrait en hold.');
            }

            $this->balanceService->releaseWithdrawalHold(
                withdrawalTx: $transaction,
                adminUserId: (int) Auth::id(),
                adminNote: $note
            );

            return back()->with('success', 'Hold levé : retrait repassé en pending.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Erreur lors du release.');
        }
    }

    /**
     * Admin - marquer payé
     */
    public function markPaid(Request $request, Transaction $transaction)
    {
        try {
            $this->assertIsWithdrawalRequest($transaction);

            if (($transaction->status ?? null) === 'hold') {
                return back()->with('error', 'Ce retrait est en HOLD. Libère-le d’abord (Release hold).');
            }

            $note = trim((string) $request->input('admin_note', ''));

            $meta = is_array($transaction->meta ?? null)
                ? $transaction->meta
                : (is_object($transaction->meta ?? null) ? (array) $transaction->meta : []);

            $needs = (bool) ($meta['needs_admin_note'] ?? false);

            if ($needs && $note === '') {
                return back()->with('error', 'Note admin obligatoire pour un retrait à risque élevé.');
            }

            if ($note === '') {
                $note = 'PayPal envoyé';
            }

            // (Optionnel, si tu ajoutes ces champs plus tard dans le blade)
            $reasonCode   = $request->input('reason_code');
            $userMessage  = $request->input('user_message');

            $this->balanceService->markWithdrawalPaid(
                withdrawalTx: $transaction,
                adminUserId: (int) Auth::id(),
                adminNote: $note,
                reasonCode: is_string($reasonCode) ? $reasonCode : null,
                userMessage: is_string($userMessage) ? $userMessage : null
            );

            return back()->with('success', 'Retrait marqué comme payé.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Erreur lors du passage en payé.');
        }
    }

    /**
     * Admin - rejeter + recrédit
     */
    public function reject(Request $request, Transaction $transaction)
    {
        try {
            $this->assertIsWithdrawalRequest($transaction);

            $note = trim((string) $request->input('admin_note', ''));

            $meta = is_array($transaction->meta ?? null)
                ? $transaction->meta
                : (is_object($transaction->meta ?? null) ? (array) $transaction->meta : []);

            $needs = (bool) ($meta['needs_admin_note'] ?? false);

            if ($needs && $note === '') {
                return back()->with('error', 'Note admin obligatoire pour un retrait à risque élevé.');
            }

            if ($note === '') {
                $note = 'Rejet admin';
            }

            // (Optionnel, si tu ajoutes ces champs plus tard dans le blade)
            $reasonCode   = $request->input('reason_code');
            $userMessage  = $request->input('user_message');

            $this->balanceService->rejectWithdrawal(
                withdrawalTx: $transaction,
                adminUserId: (int) Auth::id(),
                adminNote: $note,
                reasonCode: is_string($reasonCode) ? $reasonCode : null,
                userMessage: is_string($userMessage) ? $userMessage : null
            );

            return back()->with('success', 'Retrait rejeté et solde recrédité.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Erreur lors du rejet.');
        }
    }

    private function assertIsWithdrawalRequest(Transaction $t): void
    {
        if (($t->type ?? null) !== 'withdrawal_request') {
            throw new RuntimeException('Transaction invalide (pas une demande de retrait).');
        }
    }
}
