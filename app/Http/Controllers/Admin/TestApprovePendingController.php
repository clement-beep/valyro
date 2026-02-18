<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use Illuminate\Http\Request;

class TestApprovePendingController extends Controller
{
    public function __invoke(Request $request, BalanceService $balanceService)
    {
        $admin = $request->user();

        if (!$admin || $admin->email !== 'maillet.clement.ifsi@gmail.com') {
            abort(403);
        }

        $balanceService->approvePending($admin, 250, [
            'source' => 'admin',
            'reference' => 'APPROVE-TEST-' . now()->format('YmdHis'),
            'note' => 'Validation test pending -> solde',
        ]);

        return back()->with('success', 'Pending validé (déplacé vers solde).');
    }
}
