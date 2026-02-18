{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
@php
  $fmtPts = fn(int $pts) => number_format($pts, 0, ',', ' ') . ' pts';
  $fmtEur = fn(int $cents) => number_format($cents / 100, 2, ',', ' ') . ' €';

  $badge = function($s){
    $s = strtolower((string)$s);
    return match($s){
      'confirmed' => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
      'pending'   => 'bg-amber-500/15 text-amber-200 border border-amber-500/20',
      'rejected'  => 'bg-rose-500/15 text-rose-200 border border-rose-500/20',
      default     => 'bg-white/10 text-slate-200 border border-white/10',
    };
  };

  $label = function($s){
    $s = strtolower((string)$s);
    return match($s){
      'confirmed' => 'Confirmé',
      'pending'   => 'En attente',
      'rejected'  => 'Rejeté',
      default     => '—',
    };
  };

  $cConfirmed = (int)($convByStatus7d['confirmed'] ?? 0);
  $cPending   = (int)($convByStatus7d['pending'] ?? 0);
  $cRejected  = (int)($convByStatus7d['rejected'] ?? 0);
@endphp

<style>
:root{
  --vy-border: rgba(255,255,255,.12);
  --vy-ink: rgba(255,255,255,.92);
  --vy-muted: rgba(255,255,255,.70);
  --vy-shadow: 0 18px 55px rgba(0,0,0,.42);
  --vy-shadow2: 0 10px 30px rgba(0,0,0,.28);
}
.vy-card{
  border: 1px solid var(--vy-border);
  background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.05));
  border-radius: 26px;
  box-shadow: var(--vy-shadow);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  position: relative;
}
.vy-card:before{
  content:"";
  position:absolute;
  inset:0;
  border-radius: 26px;
  pointer-events:none;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.10), inset 0 0 0 1px rgba(255,255,255,.04);
}
.vy-card-h{
  border-bottom: 1px solid rgba(255,255,255,.10);
  background: radial-gradient(900px 220px at 10% 0%, rgba(124,58,237,.18), transparent 60%),
              radial-gradient(900px 220px at 90% 0%, rgba(217,70,239,.14), transparent 60%);
}
.vy-muted{ color: var(--vy-muted); }
.vy-btn{
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.06);
  padding: 10px 14px;
  font-weight: 800;
  color: rgba(255,255,255,.92);
  font-size: 13px;
  white-space: nowrap;
  box-shadow: var(--vy-shadow2);
}
.vy-btn:hover{ background: rgba(255,255,255,.10); }
.vy-chip{
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.08);
  color: rgba(255,255,255,.86);
}
.vy-kpi{
  border: 1px solid rgba(255,255,255,.10);
  background: rgba(2,6,23,.28);
  border-radius: 22px;
  padding: 16px;
}
.vy-kpi .n{ font-size: 22px; font-weight: 900; color: rgba(255,255,255,.95); }
.vy-kpi .t{ font-size: 12px; color: rgba(255,255,255,.70); margin-top: 2px; }
.vy-table thead th{
  background: rgba(2,6,23,.40);
  color: rgba(226,232,240,.88);
}
.vy-row:hover{ background: rgba(255,255,255,.06); }
</style>

<div class="space-y-8">
  <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <div class="text-sm vy-muted">Admin</div>
      <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">Dashboard Admin</h1>
      <p class="vy-muted mt-1 text-sm">
        Vue rapide (7 jours) : conversions, crédits, retraits, utilisateurs.
      </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <a href="{{ route('admin.conversions') }}" class="vy-btn">Conversions</a>
      <a href="{{ route('admin.withdrawals') }}" class="vy-btn">Retraits</a>
      <a href="{{ route('admin.users') }}" class="vy-btn">Utilisateurs</a>
      <a href="{{ route('dashboard') }}" class="vy-btn">Retour membre</a>
    </div>
  </div>

  <div class="vy-card overflow-hidden">
    <div class="p-6 vy-card-h flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="text-white font-semibold">Résumé global</div>
        <div class="text-sm vy-muted mt-1">Solde cumulé & pending (tous users)</div>
      </div>
      <div class="flex flex-wrap gap-2">
        <div class="vy-chip">Solde: <span class="text-white font-semibold">{{ $fmtEur((int)$sumBalanceCents) }}</span></div>
        <div class="vy-chip">Pending: <span class="text-white font-semibold">{{ $fmtEur((int)$sumPendingCents) }}</span></div>
      </div>
    </div>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="vy-kpi">
        <div class="n">{{ number_format((int)$usersTotal, 0, ',', ' ') }}</div>
        <div class="t">Utilisateurs total</div>
        <div class="mt-2 text-xs vy-muted">+{{ number_format((int)$users7d, 0, ',', ' ') }} sur 7j</div>
      </div>

      <div class="vy-kpi">
        <div class="n">{{ number_format((int)$conv7d, 0, ',', ' ') }}</div>
        <div class="t">Conversions sur 7j</div>
        <div class="mt-2 text-xs vy-muted">24h: {{ number_format((int)$conv24h, 0, ',', ' ') }} • Total: {{ number_format((int)$convTotal, 0, ',', ' ') }}</div>
      </div>

      <div class="vy-kpi">
        <div class="n">{{ $fmtPts((int)$payout7d) }}</div>
        <div class="t">Payout conversions (7j)</div>
        <div class="mt-2 text-xs vy-muted">≈ {{ number_format(((int)$payout7d)/100, 2, ',', ' ') }} €</div>
      </div>

      <div class="vy-kpi">
        <div class="n">{{ number_format((int)$withdrawRequestsPending, 0, ',', ' ') }}</div>
        <div class="t">Retraits en attente</div>
        <div class="mt-2 text-xs vy-muted">Demandes 7j: {{ number_format((int)$withdrawRequests7d, 0, ',', ' ') }}</div>
      </div>
    </div>

    <div class="px-6 pb-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="vy-kpi">
        <div class="text-white font-semibold">Conversions (7j)</div>
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
          <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badge('confirmed') }}">Confirmé: {{ number_format($cConfirmed,0,',',' ') }}</span>
          <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badge('pending') }}">Pending: {{ number_format($cPending,0,',',' ') }}</span>
          <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badge('rejected') }}">Rejeté: {{ number_format($cRejected,0,',',' ') }}</span>
        </div>
        <div class="mt-3 text-xs vy-muted">But: surveiller pending qui stagne + rejets anormaux.</div>
      </div>

      <div class="vy-kpi">
        <div class="text-white font-semibold">Crédits (transactions 7j)</div>
        <div class="mt-2 text-xs vy-muted">Pending: <span class="text-white font-semibold">{{ number_format((int)$creditsPending7d,0,',',' ') }}</span></div>
        <div class="mt-1 text-xs vy-muted">Confirmés: <span class="text-white font-semibold">{{ number_format((int)$creditsConfirmed7d,0,',',' ') }}</span></div>
        <div class="mt-1 text-xs vy-muted">Somme confirmée: <span class="text-white font-semibold">{{ $fmtEur((int)$creditsConfirmedSum7d) }}</span></div>
      </div>

      <div class="vy-kpi">
        <div class="text-white font-semibold">Retraits (transactions 7j)</div>
        <div class="mt-2 text-xs vy-muted">Payés: <span class="text-white font-semibold">{{ number_format((int)$withdrawPaid7d,0,',',' ') }}</span></div>
        <div class="mt-1 text-xs vy-muted">Somme payée: <span class="text-white font-semibold">{{ $fmtEur((int)$withdrawPaidSum7d) }}</span></div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="vy-card overflow-hidden">
      <div class="p-6 vy-card-h flex items-center justify-between gap-3">
        <div>
          <div class="text-white font-semibold">Dernières conversions</div>
          <div class="text-sm vy-muted mt-1">12 dernières (received_at)</div>
        </div>
        <a href="{{ route('admin.conversions') }}" class="vy-btn">Voir tout</a>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm vy-table">
          <thead>
            <tr>
              <th class="text-left px-6 py-3 font-medium">Date</th>
              <th class="text-left px-6 py-3 font-medium">Statut</th>
              <th class="text-left px-6 py-3 font-medium">External</th>
              <th class="text-left px-6 py-3 font-medium">User</th>
              <th class="text-left px-6 py-3 font-medium">Payout</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            @forelse($latestConversions as $c)
              @php
                $dt = $c->received_at ?? $c->created_at;
                $st = strtolower((string)($c->status ?? ''));
              @endphp
              <tr class="vy-row">
                <td class="px-6 py-4 text-white/90 whitespace-nowrap">
                  {{ optional($dt)->format('d/m/Y H:i') }}
                  <div class="text-xs vy-muted mt-1">#{{ $c->id }}</div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badge($st) }}">
                    {{ $label($st) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-white/90 font-semibold truncate max-w-[220px]">
                  {{ $c->external_id ?? '—' }}
                </td>
                <td class="px-6 py-4">
                  <div class="text-white font-semibold">{{ $c->user?->name ?? '—' }}</div>
                  <div class="text-xs vy-muted">{{ $c->user?->email ?? '—' }}</div>
                </td>
                <td class="px-6 py-4 text-white font-semibold whitespace-nowrap">
                  {{ $fmtPts((int)($c->payout_points ?? 0)) }}
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="px-6 py-10 text-center vy-muted">Aucune conversion.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="vy-card overflow-hidden">
      <div class="p-6 vy-card-h flex items-center justify-between gap-3">
        <div>
          <div class="text-white font-semibold">Dernières demandes de retrait</div>
          <div class="text-sm vy-muted mt-1">12 dernières (withdrawal_request)</div>
        </div>
        <a href="{{ route('admin.withdrawals') }}" class="vy-btn">Voir tout</a>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm vy-table">
          <thead>
            <tr>
              <th class="text-left px-6 py-3 font-medium">Date</th>
              <th class="text-left px-6 py-3 font-medium">Statut</th>
              <th class="text-left px-6 py-3 font-medium">User</th>
              <th class="text-left px-6 py-3 font-medium">Montant</th>
              <th class="text-left px-6 py-3 font-medium">Référence</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            @forelse($latestWithdrawals as $t)
              @php
                $st = strtolower((string)($t->status ?? ''));
              @endphp
              <tr class="vy-row">
                <td class="px-6 py-4 text-white/90 whitespace-nowrap">
                  {{ optional($t->created_at)->format('d/m/Y H:i') }}
                  <div class="text-xs vy-muted mt-1">#{{ $t->id }}</div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badge($st) }}">
                    {{ $label($st) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-white font-semibold">{{ $t->user?->name ?? '—' }}</div>
                  <div class="text-xs vy-muted">{{ $t->user?->email ?? '—' }}</div>
                </td>
                <td class="px-6 py-4 text-white font-semibold whitespace-nowrap">
                  {{ $fmtEur((int)($t->amount_cents ?? 0)) }}
                </td>
                <td class="px-6 py-4 text-white/90 truncate max-w-[220px]">
                  {{ $t->reference ?? '—' }}
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="px-6 py-10 text-center vy-muted">Aucune demande.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
