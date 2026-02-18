<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfferConversion;
use Illuminate\Http\Request;

class ConversionsAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $conversions = OfferConversion::query()
            ->with(['user', 'click'])
            ->when($status !== '' && in_array($status, ['pending','confirmed','rejected'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('subid', 'like', "%{$q}%")
                       ->orWhere('external_id', 'like', "%{$q}%")
                       ->orWhere('network', 'like', "%{$q}%");

                    // match user id si numérique
                    if (ctype_digit($q)) {
                        $qq->orWhere('user_id', (int) $q)
                           ->orWhere('offer_click_id', (int) $q);
                    }

                    // match email user
                    $qq->orWhereHas('user', function ($uq) use ($q) {
                        $uq->where('email', 'like', "%{$q}%");
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.conversions', [
            'conversions' => $conversions,
            'q' => $q,
            'status' => $status,
        ]);
    }
}
