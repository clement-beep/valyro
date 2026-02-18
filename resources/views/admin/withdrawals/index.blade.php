@extends('layouts.app')

@section('content')
@php
    // Convention : 1 point = 1 centime (0,01€)
    $fmtPts = fn (int $pts) => number_format($pts, 0, ',', ' ') . ' pts';
    $fmtEur = fn (int $pts) => number_format($pts / 100, 2, ',', ' ') . ' €';

    $labelStatus = function (?string $status) {
        return match($status) {
            'paid' => 'Payé',
            'rejected' => 'Rejeté',
            default => 'En attente',
        };
    };

    $badgeStatus = function (?string $status) {
        return match($status) {
            'paid' => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
            'rejected' => 'bg-rose-500/15 text-rose-200 border border-rose-500/20',
            default => 'bg-amber-500/15 text-amber-200 border border-amber-500/20',
        };
    };

    $metaArr = function ($meta): array {
        if (is_array($meta)) return $meta;
        if (is_object($meta)) return (array) $meta;
        return [];
    };

    $paypalFromTx = function ($w) use ($metaArr) {
        if (!empty($w->paypal_email)) return $w->paypal_email;
        $meta = $metaArr($w->meta ?? []);
        return $meta['paypal_email'] ?? '—';
    };

    $methodFromTx = function ($w) use ($metaArr) {
        if (!empty($w->method)) return $w->method;
        $meta = $metaArr($w->meta ?? []);
        return $meta['method'] ?? 'paypal';
    };

    $pointsFromTx = fn ($w) => (int) ($w->amount_cents ?? 0); // amount_cents = points
@endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-sm text-slate-400">Admin</div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                Échanges PayPal
            </h1>
            <p class="text-slate-400 mt-1 text-sm">
                Traite les demandes : “Payé” ou “Rejeté” (recrédit automatique des Points).
            </p>
            <p class="text-xs text-slate-500 mt-2">
                Conversion interne : <span class="text-slate-200 font-medium">1 pt = 0,01€</span>.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                Retour dashboard
            </a>
            <a href="{{ route('withdraw') }}"
               class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                Page retrait
            </a>
        </div>
    </div>

    {{-- Pending list --}}
    <div class="rounded-3xl border border-white/10 bg-white/5 overflow-hidden">
        <div class="p-6 border-b border-white/10 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">À traiter</h2>
                <p class="text-sm text-slate-400 mt-1">
                    Demandes en statut <span class="text-slate-200 font-semibold">En attente</span>.
                </p>
            </div>
            <div class="text-xs rounded-full bg-white/10 px-3 py-1 text-slate-300">
                {{ $pending->count() }} en attente
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/40 text-slate-300">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Date</th>
                        <th class="text-left px-6 py-3 font-medium">Utilisateur</th>
                        <th class="text-left px-6 py-3 font-medium">PayPal</th>
                        <th class="text-left px-6 py-3 font-medium">Montant</th>
                        <th class="text-left px-6 py-3 font-medium">Statut</th>
                        <th class="text-right px-6 py-3 font-medium">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/5">
                    @forelse($pending as $w)
                        @php
                            $dt = $w->requested_at ?? $w->created_at;
                            $status = $w->status ?? 'pending';
                            $pts = $pointsFromTx($w);
                            $paypal = $paypalFromTx($w);
                        @endphp

                        <tr class="hover:bg-white/5">
                            <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                {{ optional($dt)->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-slate-100 font-semibold">{{ $w->user?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $w->user?->email ?? '—' }}</div>
                            </td>

                            <td class="px-6 py-4 text-slate-300">
                                {{ $paypal }}
                            </td>

                            <td class="px-6 py-4 text-slate-100 font-semibold whitespace-nowrap">
                                {{ $fmtPts($pts) }}
                                <span class="text-xs font-normal text-slate-400"> (≈ {{ $fmtEur($pts) }})</span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badgeStatus($status) }}">
                                    {{ $labelStatus($status) }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">

                                    {{-- Paid --}}
                                    <form method="POST" action="{{ route('admin.withdrawals.mark-paid', $w) }}"
                                          onsubmit="return confirm('Marquer cette demande comme PAYÉE ?');">
                                        @csrf
                                        <input type="hidden" name="admin_note" value="">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm font-semibold text-emerald-200 hover:bg-emerald-500/15">
                                            Marquer payé
                                        </button>
                                    </form>

                                    {{-- Reject --}}
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}"
                                          onsubmit="return confirm('Rejeter et RECRÉDITER les points ?');">
                                        @csrf
                                        <input type="hidden" name="admin_note" value="Rejet admin">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-semibold text-rose-200 hover:bg-rose-500/15">
                                            Rejeter
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                Aucune demande en attente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 text-xs text-slate-400 border-t border-white/10">
            “Rejeter” recrédite automatiquement l’utilisateur et enregistre une transaction.
        </div>
    </div>

    {{-- Recent list --}}
    <div class="rounded-3xl border border-white/10 bg-white/5 overflow-hidden">
        <div class="p-6 border-b border-white/10 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Récents</h2>
                <p class="text-sm text-slate-400 mt-1">Les 50 dernières demandes (tous statuts).</p>
            </div>
            <div class="text-xs rounded-full bg-white/10 px-3 py-1 text-slate-300">
                {{ $recent->count() }} éléments
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/40 text-slate-300">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Date</th>
                        <th class="text-left px-6 py-3 font-medium">Utilisateur</th>
                        <th class="text-left px-6 py-3 font-medium">Méthode</th>
                        <th class="text-left px-6 py-3 font-medium">PayPal</th>
                        <th class="text-left px-6 py-3 font-medium">Montant</th>
                        <th class="text-left px-6 py-3 font-medium">Statut</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/5">
                    @forelse($recent as $w)
                        @php
                            $dt = $w->requested_at ?? $w->created_at;
                            $status = $w->status ?? 'pending';
                            $pts = $pointsFromTx($w);
                            $paypal = $paypalFromTx($w);
                            $method = $methodFromTx($w);
                        @endphp

                        <tr class="hover:bg-white/5">
                            <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                {{ optional($dt)->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-slate-100 font-semibold">{{ $w->user?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $w->user?->email ?? '—' }}</div>
                            </td>

                            <td class="px-6 py-4 text-slate-300">
                                {{ $method }}
                            </td>

                            <td class="px-6 py-4 text-slate-300">
                                {{ $paypal }}
                            </td>

                            <td class="px-6 py-4 text-slate-100 font-semibold whitespace-nowrap">
                                {{ $fmtPts($pts) }}
                                <span class="text-xs font-normal text-slate-400"> (≈ {{ $fmtEur($pts) }})</span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badgeStatus($status) }}">
                                    {{ $labelStatus($status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                Aucun retrait.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
