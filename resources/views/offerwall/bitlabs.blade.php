@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-sm text-slate-400">Offerwall</div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">BitLabs</h1>
            <p class="text-slate-400 mt-1 text-sm">
                Ton identifiant de tracking :
                <span class="font-mono text-slate-200">{{ $subid }}</span>
            </p>
        </div>

        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
            Retour dashboard
        </a>
    </div>

    @if(!$offerwallUrl)
        <div class="rounded-3xl border border-amber-500/20 bg-amber-500/10 p-6 text-amber-100">
            <div class="font-semibold">BitLabs pas encore configuré</div>
            <div class="mt-2 text-sm text-amber-100/80">
                Mets ton lien d’offerwall dans <span class="font-mono">BITLABS_OFFERWALL_URL</span> (dans .env).
                <br>
                Le paramètre de tracking utilisé sera :
                <span class="font-mono">{{ $subidParam }}</span>=<span class="font-mono">{{ $subid }}</span>
            </div>
        </div>
    @else
        <div class="rounded-3xl border border-white/10 bg-white/5 overflow-hidden">
            <div class="p-4 border-b border-white/10 text-sm text-slate-300">
                Offerwall BitLabs (embed). Si un adblock gêne, ouvre dans un nouvel onglet :
                <a href="{{ $offerwallUrl }}" target="_blank" class="text-fuchsia-200 hover:underline">ouvrir</a>
            </div>

            <iframe
                src="{{ $offerwallUrl }}"
                class="w-full"
                style="height: 75vh; border: 0;"
                referrerpolicy="no-referrer-when-downgrade"
                allow="clipboard-write; fullscreen"
            ></iframe>
        </div>
    @endif
</div>
@endsection
