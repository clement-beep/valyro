{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    $balanceCents = (int) ($balance->balance_cents ?? 0);
    $pendingCents = (int) ($balance->pending_cents ?? 0);

    // Convention UI: 1 point = 1 centime
    // Ici, dans tes transactions, amount_cents = points (comme tu l’utilises déjà partout).
    $formatCents = fn (int $cents) => number_format($cents / 100, 2, ',', ' ') . ' €';
    $formatPts   = fn (int $pts) => number_format($pts, 0, ',', ' ') . ' pts';

    $statusLabel = function (?string $status) {
        $s = strtolower((string) $status);
        return match($s) {
            'confirmed' => 'Confirmé',
            'paid'      => 'Payé',
            'rejected'  => 'Rejeté',
            'hold'      => 'En revue',
            'pending'   => 'En attente',
            default     => $status ?: 'En attente',
        };
    };

    $statusClass = function (?string $status) {
        $s = strtolower((string) $status);
        return match($s) {
            'confirmed' => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
            'paid'      => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
            'rejected'  => 'bg-rose-500/15 text-rose-200 border border-rose-500/20',
            'hold'      => 'bg-indigo-500/15 text-indigo-200 border border-indigo-500/20',
            default     => 'bg-amber-500/15 text-amber-200 border border-amber-500/20',
        };
    };

    $typeLabelEarn = function (?string $type) {
        $t = strtolower((string) $type);
        return match($t) {
            'pending_credit'     => 'Crédit (en attente)',
            'pending_approved'   => 'Validation (pending → confirmé)',
            'confirmed_credit'   => 'Crédit (confirmé)',
            'postback_rejected'  => 'Postback rejeté',
            'postback_chargeback'=> 'Chargeback',
            default              => $type ?: '—',
        };
    };

    // Ici on n’affiche QUE les entrées (gains)
    $isPlus = fn(string $type) => in_array(strtolower($type), [
        'pending_credit',
        'pending_approved',
        'confirmed_credit',
    ], true);
@endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-sm text-slate-400">Dashboard</div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                Bienvenue, {{ $user->name }}.
            </h1>
            <p class="text-slate-400 mt-1 text-sm">
                Suis tes gains (offres) et tes validations. Les retraits sont sur la page “Échanges”.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('offers') }}"
               class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:opacity-95">
                Voir les offres
            </a>

            <a href="{{ route('withdraw') }}"
               class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                Échanges
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Solde confirmé --}}
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <div class="text-sm text-slate-400">Solde disponible</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold">{{ $formatCents($balanceCents) }}</div>
                <div class="text-xs rounded-full bg-white/10 px-3 py-1 text-slate-300">EUR</div>
            </div>
            <div class="mt-3 text-xs text-slate-400">
                Points confirmés et retirables.
            </div>
        </div>

        {{-- Pending --}}
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <div class="text-sm text-slate-400">En attente (offres)</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold">{{ $formatCents($pendingCents) }}</div>
                <div class="text-xs rounded-full bg-indigo-500/15 px-3 py-1 text-indigo-200 border border-indigo-500/20">Pending</div>
            </div>
            <div class="mt-3 text-xs text-slate-400">
                Crédits en validation (postback partenaire).
            </div>
        </div>

        {{-- Actions --}}
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <div class="text-sm text-slate-400">Actions rapides</div>

            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('offers') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                    Ouvrir l’offerwall
                </a>

                <a href="{{ route('withdraw') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                    Aller aux échanges
                </a>
            </div>

            <div class="mt-4 text-xs text-slate-400">
                Astuce : “En attente” concerne uniquement les offres (pas les retraits).
            </div>
        </div>
    </div>

    {{-- GAINS / VALIDATIONS --}}
    <div class="rounded-3xl border border-white/10 bg-white/5 overflow-hidden">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold">Gains & validations d’offres</h2>
                    <p class="text-sm text-slate-400 mt-1">Crédits reçus par postback (pending / confirmé).</p>
                </div>
                <div class="text-xs rounded-full bg-white/10 px-3 py-1 text-slate-300">
                    Offres
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/40 text-slate-300">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Date</th>
                        <th class="text-left px-6 py-3 font-medium">Type</th>
                        <th class="text-left px-6 py-3 font-medium">Statut</th>
                        <th class="text-left px-6 py-3 font-medium">Source</th>
                        <th class="text-left px-6 py-3 font-medium">Réf / Note</th>
                        <th class="text-right px-6 py-3 font-medium">Montant</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/5">
                    @forelse($earnTransactions as $t)
                        @php
                            $date = $t->occurred_at ?? $t->created_at;
                            $rawStatus = $t->status ?? 'pending';
                            $type = (string) ($t->type ?? '');
                            $sign = $isPlus($type) ? '+' : '';
                            $amtPts = (int) ($t->amount_cents ?? 0); // ici = points
                        @endphp

                        <tr class="hover:bg-white/5">
                            <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                {{ optional($date)->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white/10 text-slate-100">
                                    {{ $typeLabelEarn($t->type ?? null) }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $statusClass($rawStatus) }}">
                                    {{ $statusLabel($rawStatus) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-slate-300">
                                {{ $t->source ?? '—' }}
                            </td>

                            <td class="px-6 py-4 text-slate-300">
                                <div class="font-medium text-slate-100">
                                    {{ $t->reference ?? '—' }}
                                </div>
                                @if(!empty($t->note))
                                    <div class="text-xs text-slate-400 mt-1">
                                        {{ $t->note }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right font-semibold text-slate-100 whitespace-nowrap">
                                {{ $sign }}{{ $formatPts($amtPts) }}
                                <span class="text-xs font-normal text-slate-400"> ({{ $formatCents($amtPts) }})</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                Aucun gain d’offre pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 text-xs text-slate-400 border-t border-white/10">
            Ici : uniquement les crédits d’offres (pending / confirmé).
        </div>
    </div>

</div>
@endsection
