<?php

namespace App\Http\Controllers;

use App\Models\OfferClick;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BitlabsOfferwallController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        // subid unique, traçable
        $subid = 'vly_' . Str::lower(Str::random(20));

        // On enregistre un "click" générique offerwall (offer_id = null)
        OfferClick::create([
            'user_id' => $user->id,
            'offer_id' => null, // ✅ IMPORTANT: accepter null dans migration offer_clicks si pas déjà
            'subid' => $subid,
            'ip' => (string) $request->ip() ?: null,
            'user_agent' => (string) $request->userAgent() ?: null,
            'referrer' => (string) $request->headers->get('referer') ?: null,
            'risk_score' => 0,
            'risk_flags' => null,
            'started_at' => now(),
        ]);

        $base = (string) config('bitlabs.offerwall_url', '');
        if ($base === '') {
            // Tant que t’as pas le lien bitlabs, on affiche une page “setup”
            return view('offerwall.bitlabs', [
                'offerwallUrl' => null,
                'subid' => $subid,
                'subidParam' => config('bitlabs.subid_param', 'subid'),
            ]);
        }

        $param = (string) config('bitlabs.subid_param', 'subid');
        $sep = str_contains($base, '?') ? '&' : '?';
        $offerwallUrl = $base . $sep . $param . '=' . urlencode($subid);

        return view('offerwall.bitlabs', [
            'offerwallUrl' => $offerwallUrl,
            'subid' => $subid,
            'subidParam' => $param,
        ]);
    }
}
