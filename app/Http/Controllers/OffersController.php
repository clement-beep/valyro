<?php

namespace App\Http\Controllers;

use App\Models\OfferClick;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OffersController extends Controller
{
    public function index(Request $request)
    {
        $provider = (string) config('valyro.offerwall.provider', 'bitlabs');
        $url = (string) config('valyro.offerwall.url', '');

        return view('offers', compact('provider', 'url'));
    }

    public function startOfferwall(Request $request)
    {
        $user = $request->user();

        $baseUrl = (string) config('valyro.offerwall.url', '');
        if (trim($baseUrl) === '') {
            return back()->with('error', "Offerwall non configurée : BITLABS_OFFERWALL_URL est vide.");
        }

        $subid = 'vly_' . $user->id . '_' . Str::random(16);

        OfferClick::create([
            'user_id' => $user->id,
            'offer_id' => null,
            'subid' => $subid,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'referrer' => (string) $request->headers->get('referer', ''),
            'risk_score' => 0,
            'risk_flags' => null,
            'started_at' => now(),
        ]);

        $param = (string) config('valyro.offerwall.subid_param', 'subid');

        // Support placeholder {subid}
        if (str_contains($baseUrl, '{subid}')) {
            $finalUrl = str_replace('{subid}', urlencode($subid), $baseUrl);
            return redirect()->away($finalUrl);
        }

        // Sinon on append ?subid=...
        $glue = str_contains($baseUrl, '?') ? '&' : '?';
        $finalUrl = $baseUrl . $glue . urlencode($param) . '=' . urlencode($subid);

        return redirect()->away($finalUrl);
    }
}
