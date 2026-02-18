@extends('layouts.app')

@section('content')
@php
    // Convention: 1 point = 1 centime
    $balancePts = (int) ($balance->balance_cents ?? 0);
    $pendingPts = (int) ($balance->pending_cents ?? 0);

    $formatPts = fn (int $pts) => number_format($pts, 0, ',', ' ') . ' pts';
    $formatEur = fn (int $pts) => number_format($pts / 100, 2, ',', ' ') . ' €';

    $authUser = auth()->user();
    $isAdmin = ($authUser?->email ?? null) === config('valyro.admin.email');

    // ✅ Badges RETRAITS (sorties)
    $labelWStatus = function (?string $status) {
        $s = strtolower((string) $status);
        return match($s) {
            'paid'     => 'Payé',
            'rejected' => 'Rejeté',
            'hold'     => 'En revue',
            'pending'  => 'En attente',
            default    => 'En attente',
        };
    };

    $badgeWStatus = function (?string $status) {
        $s = strtolower((string) $status);
        return match($s) {
            'paid'     => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
            'rejected' => 'bg-rose-500/15 text-rose-200 border border-rose-500/20',
            'hold'     => 'bg-amber-500/15 text-amber-200 border border-amber-500/20',
            default    => 'bg-white/10 text-slate-200 border border-white/10',
        };
    };

    $minPts = (int) config('valyro.points.min_withdraw', 1000);
    $maxSinglePts = (int) config('valyro.points.max_single', 5000);
    $maxPerDayPts = (int) config('valyro.points.max_per_day', 5000);
    $maxCountDay  = (int) config('valyro.points.max_count_day', 2);
    $cooldownH    = (int) config('valyro.points.cooldown_h', 24);

    $hasPendingWithdrawal = (bool) ($hasPendingWithdrawal ?? false);
    $canWithdraw = ($balancePts >= $minPts) && (!$hasPendingWithdrawal);

    $defaultPts = $canWithdraw ? $minPts : max(0, $balancePts);
    $maxInput = max(0, min($balancePts, $maxSinglePts));

    $giftcards = [
        ['name' => 'Amazon', 'hint' => 'Très demandé', 'disabled' => true],
        ['name' => 'Steam', 'hint' => 'Gaming PC', 'disabled' => true],
        ['name' => 'Apple', 'hint' => 'App Store', 'disabled' => true],
        ['name' => 'PlayStation', 'hint' => 'PS Store', 'disabled' => true],
        ['name' => 'Xbox', 'hint' => 'Microsoft Store', 'disabled' => true],
        ['name' => 'Google Play', 'hint' => 'Android', 'disabled' => true],
        ['name' => 'Netflix', 'hint' => 'Streaming', 'disabled' => true],
        ['name' => 'Spotify', 'hint' => 'Musique', 'disabled' => true],
    ];

    $withdrawals = $withdrawals ?? collect();
@endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-sm text-slate-400">Échanges</div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                Retraits PayPal & Cartes cadeaux
            </h1>
            <p class="text-slate-400 mt-1 text-sm">
                Page dédiée uniquement aux <span class="text-slate-100 font-semibold">sorties</span> (retraits).
                Les gains/validations d’offres sont visibles sur le <span class="text-slate-100 font-semibold">Dashboard</span>.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                Retour dashboard
            </a>

            @if($isAdmin && \Illuminate\Support\Facades\Route::has('admin.withdrawals'))
                <a href="{{ route('admin.withdrawals') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2 text-sm font-semibold text-indigo-200 hover:bg-indigo-500/15">
                    Admin échanges
                </a>
            @endif
        </div>
    </div>

    {{-- Main grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">

        {{-- PayPal withdrawal form --}}
        <div class="lg:col-span-2 rounded-3xl border border-white/10 bg-white/5 p-6">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm text-slate-400">Solde retirable (confirmé)</div>
                    <div class="mt-2 text-3xl font-semibold">
                        {{ $formatPts($balancePts) }}
                        <span class="text-sm font-normal text-slate-400"> (≈ {{ $formatEur($balancePts) }})</span>
                    </div>

                    <div class="mt-2 text-xs text-slate-400">
                        Les points en attente (offres en validation) ne sont pas retirables :
                        <span class="text-slate-200 font-medium">{{ $formatPts($pendingPts) }}</span>
                        (≈ {{ $formatEur($pendingPts) }}).
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        Conversion : <span class="text-slate-200 font-medium">1 point = 0,01€</span>.
                    </div>
                </div>

                <div class="text-xs rounded-full bg-white/10 px-3 py-1 text-slate-300">
                    Min {{ number_format($minPts, 0, ',', ' ') }} pts
                </div>
            </div>

            {{-- Rules --}}
            <div class="mt-6 rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-sm font-semibold text-slate-100">
                        Règles d’échange
                    </div>
                    <div class="text-xs text-slate-300 flex flex-wrap gap-x-3 gap-y-1">
                        <span class="inline-flex items-center gap-2">
                            <span class="text-slate-400">Cooldown</span>
                            <span class="font-semibold text-slate-100">{{ $cooldownH }}h</span>
                        </span>
                        <span class="text-slate-600">•</span>
                        <span class="inline-flex items-center gap-2">
                            <span class="text-slate-400">Jusqu’à</span>
                            <span class="font-semibold text-slate-100">{{ $maxCountDay }}/jour</span>
                        </span>
                        <span class="text-slate-600">•</span>
                        <span class="inline-flex items-center gap-2">
                            <span class="text-slate-400">Max</span>
                            <span class="font-semibold text-slate-100">{{ number_format($maxSinglePts/100, 0, ',', ' ') }}€</span>
                            <span class="text-slate-500">(par retrait)</span>
                        </span>
                        <span class="text-slate-600">•</span>
                        <span class="inline-flex items-center gap-2">
                            <span class="text-slate-400">Max</span>
                            <span class="font-semibold text-slate-100">{{ number_format($maxPerDayPts/100, 0, ',', ' ') }}€</span>
                            <span class="text-slate-500">/jour</span>
                        </span>
                    </div>
                </div>
            </div>

            @if($hasPendingWithdrawal)
                <div class="mt-6 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-amber-100">
                    <div class="text-sm font-semibold">Retrait déjà en cours</div>
                    <div class="mt-1 text-xs text-amber-100/80">
                        Tu as déjà un retrait en cours. Attends qu’il soit traité.
                    </div>
                </div>
            @elseif(!$canWithdraw)
                <div class="mt-6 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-amber-100">
                    <div class="text-sm font-semibold">Solde insuffisant</div>
                    <div class="mt-1 text-xs text-amber-100/80">
                        Il te faut au moins <span class="font-semibold">{{ $formatPts($minPts) }}</span> pour demander un retrait.
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('withdraw.store') }}" class="mt-6 space-y-5" id="withdrawForm">
                @csrf

                {{-- ✅ évite une 500 si la vue n'existe pas --}}
                @includeIf('components.device-fingerprint')

                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                    <div class="text-sm font-semibold text-slate-100">Retrait PayPal</div>
                    <div class="mt-1 text-xs text-slate-400">
                        Traitement manuel. En cas de rejet, les points sont recrédités.
                    </div>
                </div>

                {{-- Amount --}}
                <div>
                    <div class="flex items-end justify-between gap-3">
                        <label class="block text-sm font-medium text-slate-200">Montant (Points Valyro)</label>
                        <div class="text-xs text-slate-400">
                            Équiv. :
                            <span class="font-semibold text-slate-100" id="eurPreview">
                                {{ $formatEur(old('amount_points') ? (int) old('amount_points') : $defaultPts) }}
                            </span>
                        </div>
                    </div>

                    <input
                        type="number"
                        name="amount_points"
                        id="amount_points"
                        step="1"
                        min="{{ $minPts }}"
                        max="{{ $maxInput }}"
                        inputmode="numeric"
                        placeholder="Ex: {{ number_format($minPts, 0, ',', ' ') }}"
                        value="{{ old('amount_points', $defaultPts) }}"
                        class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/30 px-4 py-3 text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        required
                        @disabled(!$canWithdraw)
                    />

                    <div class="mt-2 flex flex-wrap gap-2">
                        @php $quick = [1000, 2000, 5000]; @endphp
                        @foreach($quick as $q)
                            <button
                                type="button"
                                class="rounded-xl border border-white/10 bg-slate-950/20 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed"
                                data-quick="{{ $q }}"
                                @disabled(!$canWithdraw || $q > $maxInput || $q > $balancePts)
                            >
                                {{ number_format($q, 0, ',', ' ') }} pts
                            </button>
                        @endforeach

                        <button
                            type="button"
                            class="rounded-xl border border-white/10 bg-slate-950/20 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed"
                            data-quick="{{ $maxInput }}"
                            @disabled(!$canWithdraw || $maxInput < $minPts)
                            title="Mettre le max possible (solde ou limite)"
                        >
                            Max ({{ $formatPts($maxInput) }})
                        </button>
                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Les points sont déduits immédiatement pour éviter les doubles demandes.
                    </p>

                    <div class="mt-2 hidden rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-xs text-rose-100" id="amountError"></div>
                </div>

                {{-- PayPal email --}}
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-2">Email PayPal</label>
                    <input
                        type="email"
                        name="paypal_email"
                        placeholder="ton@email.com"
                        value="{{ old('paypal_email', $authUser?->email ?? '') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-950/30 px-4 py-3 text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        required
                        @disabled(!$canWithdraw)
                    />
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-fuchsia-500 px-4 py-3 text-sm font-semibold text-white hover:opacity-95 disabled:opacity-40 disabled:cursor-not-allowed"
                    @disabled(!$canWithdraw)
                >
                    Demander un retrait PayPal
                </button>

                <div class="text-xs text-slate-500">
                    En envoyant la demande, tu acceptes les conditions d’échange (anti-fraude, vérification, délais).
                </div>
            </form>
        </div>

        {{-- Gift cards (placeholder UI) --}}
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm text-slate-400">Cartes cadeaux</div>
                    <div class="mt-1 text-lg font-semibold">Bientôt disponible</div>
                    <div class="mt-1 text-sm text-slate-400">
                        Ici il y aura la demande de carte cadeau (même logique qu’un retrait).
                    </div>
                </div>
                <div class="text-xs rounded-full bg-white/10 px-3 py-1 text-slate-300">Soon</div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
                @foreach($giftcards as $g)
                    <button
                        type="button"
                        disabled
                        class="rounded-2xl border border-white/10 bg-slate-950/20 p-3 hover:bg-white/10 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <div class="text-center">
                            <div class="text-sm font-semibold text-slate-100">{{ $g['name'] }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $g['hint'] }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Historique des retraits --}}
    <div class="rounded-3xl border border-amber-500/15 bg-amber-500/5 overflow-hidden">
        <div class="p-6 border-b border-amber-500/20">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold">Historique des retraits</h2>
                    <p class="text-sm text-slate-400 mt-1">Tes 20 dernières demandes de retrait.</p>
                </div>
                <div class="text-xs rounded-full bg-amber-500/15 px-3 py-1 text-amber-200 border border-amber-500/20">
                    Sorties
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/40 text-slate-300">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Date</th>
                    <th class="text-left px-6 py-3 font-medium">Méthode</th>
                    <th class="text-left px-6 py-3 font-medium">PayPal</th>
                    <th class="text-left px-6 py-3 font-medium">Statut</th>
                    <th class="text-right px-6 py-3 font-medium">Montant</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-white/5">
                @forelse($withdrawals as $w)
                    @php
                        $dt = $w->requested_at ?? $w->created_at;
                        $status = strtolower((string) ($w->status ?? 'pending'));
                        $amountPts = (int) ($w->amount_cents ?? 0);
                        $userMessage = (string) ($w->user_message ?? '');
                    @endphp

                    <tr class="hover:bg-white/5 align-top">
                        <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                            {{ optional($dt)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-slate-300">
                            {{ $w->method ?? 'paypal' }}
                        </td>
                        <td class="px-6 py-4 text-slate-300">
                            {{ $w->paypal_email ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badgeWStatus($status) }}">
                                {{ $labelWStatus($status) }}
                            </span>

                            @if($status === 'rejected')
                                <div class="mt-2 text-xs text-rose-200/90">
                                    <span class="font-semibold">Motif :</span>
                                    {{ $userMessage !== '' ? $userMessage : "Votre demande a été refusée après vérification." }}
                                </div>
                            @elseif($status === 'paid' && $userMessage !== '')
                                <div class="mt-2 text-xs text-emerald-200/90">
                                    <span class="font-semibold">Info :</span> {{ $userMessage }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-slate-100 whitespace-nowrap">
                            -{{ $formatPts($amountPts) }}
                            <span class="text-xs font-normal text-slate-400"> (≈ {{ $formatEur($amountPts) }})</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                            Aucun retrait pour le moment.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 text-xs text-slate-400 border-t border-amber-500/20">
            Les gains/validations d’offres sont visibles sur le Dashboard (séparés des retraits).
        </div>
    </div>

</div>

<script>
(function () {
  const input = document.getElementById('amount_points');
  const eurPreview = document.getElementById('eurPreview');
  const amountError = document.getElementById('amountError');

  if (!input || !eurPreview) return;

  const min = parseInt(input.getAttribute('min') || '0', 10);
  const max = parseInt(input.getAttribute('max') || '0', 10);

  const fmtEur = (pts) => {
    const eur = (pts || 0) / 100;
    return eur.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  };

  const showError = (msg) => {
    if (!amountError) return;
    if (!msg) {
      amountError.classList.add('hidden');
      amountError.textContent = '';
      return;
    }
    amountError.classList.remove('hidden');
    amountError.textContent = msg;
  };

  const clampAndRender = () => {
    let v = parseInt(String(input.value || '').replace(/\D/g,''), 10);
    if (isNaN(v)) v = 0;

    eurPreview.textContent = fmtEur(v);

    if (v > 0 && v < min) {
      showError('Minimum : ' + min.toLocaleString('fr-FR') + ' pts.');
    } else if (max > 0 && v > max) {
      showError('Maximum : ' + max.toLocaleString('fr-FR') + ' pts.');
    } else {
      showError('');
    }
  };

  input.addEventListener('input', clampAndRender);
  clampAndRender();

  document.querySelectorAll('[data-quick]').forEach(btn => {
    btn.addEventListener('click', () => {
      const q = parseInt(btn.getAttribute('data-quick') || '0', 10);
      if (!q) return;
      input.value = q;
      clampAndRender();
      input.focus();
    });
  });
})();
</script>
@endsection
