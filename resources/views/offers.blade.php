@extends('layouts.app')

@section('content')
@php
    $providerLabel = match(strtolower((string)($provider ?? 'bitlabs'))) {
        'bitlabs' => 'BitLabs',
        default => ucfirst((string)($provider ?? 'Offerwall')),
    };
    $isConfigured = !empty($url);
@endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-sm text-slate-400">Offres</div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                Offerwall {{ $providerLabel }}
            </h1>
            <p class="text-slate-400 mt-1 text-sm">
                Ouvre l’offerwall, choisis une mission, puis complète l’offre. Le crédit arrive après validation partenaire.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                Retour dashboard
            </a>

            <a href="{{ route('withdraw') }}"
               class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:opacity-95">
                Échanges
            </a>
        </div>
    </div>

    {{-- Main card --}}
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 lg:p-8 relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none opacity-40"
             style="background: radial-gradient(900px 260px at 18% 0%, rgba(217,70,239,.16), transparent 60%);"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">

            {{-- Left --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-5">
                    <div class="text-sm text-slate-300 font-semibold">Accès à l’offerwall</div>
                    <div class="mt-2 text-sm text-slate-400">
                        Clique pour ouvrir l’offerwall dans un nouvel onglet.
                    </div>

                    <div class="mt-5">
                        <form method="POST" action="{{ route('offerwall.start') }}" target="_blank">
                            @csrf
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-fuchsia-500 px-4 py-3 text-sm font-semibold text-white hover:opacity-95">
                                Ouvrir l’Offerwall
                            </button>
                        </form>

                        @if(!$isConfigured)
                            <div class="mt-3 text-xs text-amber-200/90">
                                ⚠️ Offerwall non configurée : ajoute l’URL BitLabs dans <span class="font-mono">BITLABS_OFFERWALL_URL</span>.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-5">
                    <div class="text-sm text-slate-300 font-semibold">Bon à savoir</div>
                    <ul class="mt-2 space-y-2 text-sm text-slate-400 list-disc pl-5">
                        <li>La validation peut prendre de quelques minutes à plusieurs jours selon l’offre.</li>
                        <li>Respecte exactement les conditions (pays, appareil, étapes demandées).</li>
                        <li>Évite de multiplier les comptes/appareils pour garder un historique propre.</li>
                    </ul>
                </div>
            </div>

            {{-- Right / Tips --}}
            <div class="space-y-4">
                <div>
                    <div class="text-sm text-slate-400">Conseils</div>
                    <div class="mt-1 text-lg font-semibold">Maximiser tes gains</div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                    <div class="font-semibold text-slate-100 text-sm">1 device / 1 compte</div>
                    <div class="mt-1 text-xs text-slate-400">
                        Évite de changer souvent d’IP ou d’appareil.
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                    <div class="font-semibold text-slate-100 text-sm">Lis les conditions</div>
                    <div class="mt-1 text-xs text-slate-400">
                        Certaines offres demandent une action précise.
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                    <div class="font-semibold text-slate-100 text-sm">Validation = délai</div>
                    <div class="mt-1 text-xs text-slate-400">
                        La validation peut varier selon l’annonceur.
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ route('withdraw') }}"
                       class="w-full inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                        Aller aux échanges
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
