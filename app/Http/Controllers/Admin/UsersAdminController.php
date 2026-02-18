<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class UsersAdminController extends Controller
{
    public function __construct(private readonly BalanceService $balanceService)
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('balance')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('id', (int) $q);
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users', 'q'));
    }

    public function credit(Request $request, User $user)
    {
        $data = $request->validate([
            'amount_points' => ['required', 'integer', 'min:1', 'max:500000'],
            'note' => ['nullable', 'string', 'max:160'],
            'mode' => ['required', 'in:confirmed,pending'],
        ]);

        try {
            $amount = (int) $data['amount_points'];
            $note = trim((string) ($data['note'] ?? ''));

            if ($data['mode'] === 'pending') {
                $this->balanceService->creditPending(
                    userId: (int) $user->id,
                    amountPoints: $amount,
                    source: 'admin',
                    reference: null,
                    note: $note !== '' ? $note : 'Crédit admin (test)',
                    meta: ['admin_user_id' => (int) Auth::id()]
                );
                return back()->with('success', "✅ Crédit en attente ajouté : {$amount} pts pour {$user->email}");
            }

            $this->balanceService->creditConfirmed(
                userId: (int) $user->id,
                amountPoints: $amount,
                source: 'admin',
                reference: null,
                note: $note !== '' ? $note : 'Crédit admin (test)',
                meta: ['admin_user_id' => (int) Auth::id()]
            );

            return back()->with('success', "✅ Solde crédité : {$amount} pts pour {$user->email}");
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', "Erreur pendant le crédit.");
        }
    }
}
