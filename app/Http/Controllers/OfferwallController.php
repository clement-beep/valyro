<?php

namespace App\Http\Controllers;

use App\Models\OfferClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OfferwallController extends Controller
{
    /**
     * Start BitLabs offerwall:
     * - génère un subid unique
     * - crée un OfferClick (offer_id nullable)
     * - redirect vers l'URL offerwall (placeholder configurable)
     */
    public function start(Request $request)
    {
        $user = $request->user();

        // 1) Générer un subid unique
        $subid = $this->generateUniqueSubid();

        // 2) Créer le click (tracking interne)
        // NOTE: on reste minimal pour éviter les erreurs si certaines colonnes n'existent pas
        $click = new OfferClick();
        $click->user_id = (int) $user->id;
        $click->offer_id = null; // offerwall global, pas une offre DB
        $click->subid = $subid;

        // Si tu as ces champs dans offer_clicks, tu peux les garder :
        if (property_exists($click, 'risk_score')) $click->risk_score = 0;
        if (property_exists($click, 'risk_flags')) $click->risk_flags = null;

        $click->save();

        // 3) Construire l'URL BitLabs (placeholder)
        // Tu mettras plus tard la vraie URL BitLabs dans .env
        $urlTemplate = (string) config('valyro.offerwall.url', '');

        if ($urlTemplate === '') {
            // Pas d'URL configurée => on ne casse pas, on renvoie sur /offers avec message.
            return redirect()
                ->route('offers')
                ->with('warning', 'Offerwall non configuré (URL BitLabs manquante). Ajoute BITLABS_OFFERWALL_URL dans le .env.');
        }

        // Support d’un template {subid}
        $finalUrl = $this->injectSubidIntoUrl($urlTemplate, $subid);

        // Debug léger (optionnel)
        Log::info('Offerwall start', [
            'user_id' => $user->id,
            'subid' => $subid,
            'url' => $finalUrl,
        ]);

        return redirect()->away($finalUrl);
    }

    private function generateUniqueSubid(): string
    {
        // Format lisible + très faible probabilité collision
        // Exemple: vly_01J... (ULID)
        for ($i = 0; $i < 5; $i++) {
            $subid = 'vly_' . (string) Str::ulid();

            $exists = OfferClick::query()
                ->where('subid', $subid)
                ->exists();

            if (!$exists) return $subid;
        }

        // Fallback (extrême)
        return 'vly_' . Str::random(32);
    }

    private function injectSubidIntoUrl(string $urlTemplate, string $subid): string
    {
        // 1) Si template contient {subid} => remplacement direct
        if (str_contains($urlTemplate, '{subid}')) {
            return str_replace('{subid}', urlencode($subid), $urlTemplate);
        }

        // 2) Sinon on ajoute ?subid=... (ou &subid=...)
        $sep = str_contains($urlTemplate, '?') ? '&' : '?';
        return $urlTemplate . $sep . 'subid=' . urlencode($subid);
    }
}
