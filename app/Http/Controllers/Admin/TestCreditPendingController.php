<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use Illuminate\Http\Request;

class TestCreditPendingController extends Controller
{
    public function __invoke(Request $request, BalanceService $balanceService)
    {
        $admin = $request->user();

        if (!$admin || $admin->email !== 'maillet.clement.ifsi@gmail.com') {
            abort(403);
        }

        $balanceService->credit($admin, 250, 'pending', [
            'type' => 'earn',
            'source' => 'manual',
            'reference' => 'PENDING-TEST-' . now()->format('YmdHis'),
            'note' => 'Crédit test (pending)',
        ]);

        return back()->with('success', 'Crédit test ajouté en attente (+2,50€ pending).');
    }
}
