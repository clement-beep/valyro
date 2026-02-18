{{-- resources/views/admin/withdrawals.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    // Convention: 1 point = 1 centime => 100 pts = 1€
    $fmtPts = fn(int $pts) => number_format($pts, 0, ',', ' ') . ' pts';
    $fmtEur = fn(int $pts) => number_format($pts / 100, 2, ',', ' ') . ' €';

    $labelStatus = fn(?string $s) => match ($s) {
        'paid' => 'Payé',
        'rejected' => 'Rejeté',
        'hold' => 'En revue',
        default => 'En attente',
    };

    $badgeStatus = fn(?string $s) => match ($s) {
        'paid' => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
        'rejected' => 'bg-rose-500/15 text-rose-200 border border-rose-500/20',
        'hold' => 'bg-indigo-500/15 text-indigo-200 border border-indigo-500/20',
        default => 'bg-amber-500/15 text-amber-200 border border-amber-500/20',
    };

    $riskLabel = fn(string $lvl) => match ($lvl) {
        'high' => 'HIGH',
        'medium' => 'MED',
        default => 'LOW',
    };

    $riskBadge = fn(string $lvl) => match ($lvl) {
        'high' => 'bg-rose-500/15 text-rose-200 border border-rose-500/20',
        'medium' => 'bg-amber-500/15 text-amber-200 border border-amber-500/20',
        default => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
    };

    $whenFromTx = function ($tx) {
        return $tx->requested_at ?? $tx->occurred_at ?? $tx->created_at;
    };

    $pending = $pending ?? collect(); // À traiter (pending + hold)
    $recent  = $recent  ?? collect();

    $countPending = $pending->count();
    $countRecent  = $recent->count();

    $sumPendingPts = (int) $pending->sum(fn($t) => (int) ($t->amount_cents ?? 0));
    $sumRecentPts  = (int) $recent->sum(fn($t) => (int) ($t->amount_cents ?? 0));
@endphp

<style>
:root{
  --vy-border: rgba(255,255,255,.12);
  --vy-border2: rgba(245,197,66,.14);
  --vy-bg: rgba(255,255,255,.06);
  --vy-bg2: rgba(255,255,255,.09);
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
.vy-label{ color: rgba(255,255,255,.76); font-size: 12px; }

.vy-field, .vy-select{
  width: 100%;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(2,6,23,.28);
  padding: 10px 12px;
  color: var(--vy-ink) !important;
  font-size: 14px;
  caret-color: rgba(245,197,66,.95);
}
.vy-field::placeholder{ color: rgba(148,163,184,.88); }
.vy-field:focus, .vy-select:focus{
  outline:none;
  box-shadow: 0 0 0 2px rgba(124,58,237,.35);
  border-color: rgba(255,255,255,.22);
}
.vy-field::selection{
  background: rgba(245,197,66,.35);
  color: rgba(12,10,22,.95);
}

.vy-field:-webkit-autofill{
  -webkit-text-fill-color: rgba(255,255,255,.92) !important;
  transition: background-color 9999s ease-in-out 0s;
  box-shadow: 0 0 0px 1000px rgba(2,6,23,.28) inset !important;
  border: 1px solid rgba(255,255,255,.12) !important;
}

.vy-select option{
  background-color: rgb(15,23,42) !important;
  color: rgb(226,232,240) !important;
}

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

.vy-btn-indigo{ border-color: rgba(99,102,241,.28); background: rgba(99,102,241,.14); color: rgba(199,210,254,.98); }
.vy-btn-emerald{ border-color: rgba(16,185,129,.28); background: rgba(16,185,129,.14); color: rgba(167,243,208,.98); }
.vy-btn-rose{ border-color: rgba(244,63,94,.28); background: rgba(244,63,94,.14); color: rgba(254,202,202,.98); }

.vy-pill{
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.08);
  color: rgba(255,255,255,.86);
}

.vy-table thead th{
  background: rgba(2,6,23,.40);
  color: rgba(226,232,240,.88);
}

.vy-row:hover{ background: rgba(255,255,255,.06); }

.vy-mini-btn{
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.06);
  padding: 6px 10px;
  font-weight: 750;
  color: rgba(255,255,255,.90);
  font-size: 12px;
}
.vy-mini-btn:hover{ background: rgba(255,255,255,.10); }
</style>

<script>
  function copyText(text){
    if(!text) return;
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text);
      return;
    }
    const t = document.createElement("textarea");
    t.value = text;
    t.style.position = "fixed";
    t.style.left = "-9999px";
    t.style.top = "-9999px";
    document.body.appendChild(t);
    t.focus();
    t.select();
    try { document.execCommand("copy"); } catch(e) {}
    document.body.removeChild(t);
  }

  function norm(s){ return (s ?? "").toString().toLowerCase().trim(); }

  function statusRank(s){
    s = norm(s);
    if(s === 'hold') return 0;
    if(s === 'pending') return 1;
    if(s === 'paid') return 2;
    if(s === 'rejected') return 3;
    return 9;
  }

  function applyFilters(sectionId){
    const root = document.getElementById(sectionId);
    if(!root) return;

    const q = norm(root.querySelector('[data-filter="q"]')?.value);
    const st = norm(root.querySelector('[data-filter="status"]')?.value || 'all');
    const sort = norm(root.querySelector('[data-filter="sort"]')?.value || 'date_desc');

    const tbody = root.querySelector('tbody[data-tbody="rows"]');
    const emptyRow = root.querySelector('[data-empty="row"]');
    const countBadge = root.querySelector('[data-count="visible"]');
    if(!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr[data-row="tx"]'));
    let visible = [];

    for(const r of rows){
      const rowStatus = norm(r.dataset.status || 'pending');
      const hay = norm(r.dataset.search || '');
      const okStatus = (st === 'all') ? true : rowStatus === st;
      const okQuery  = !q ? true : hay.includes(q);

      if(okStatus && okQuery){
        r.classList.remove('hidden');
        visible.push(r);
      } else {
        r.classList.add('hidden');
      }
    }

    const getDate = (r) => parseInt(r.dataset.ts || '0', 10);
    const getAmt  = (r) => parseInt(r.dataset.amount || '0', 10);
    const getStat = (r) => statusRank(r.dataset.status || '');

    visible.sort((a,b) => {
      switch(sort){
        case 'date_asc':    return getDate(a) - getDate(b);
        case 'amount_desc': return getAmt(b)  - getAmt(a);
        case 'amount_asc':  return getAmt(a)  - getAmt(b);
        case 'status_asc':  return getStat(a) - getStat(b);
        case 'status_desc': return getStat(b) - getStat(a);
        default:            return getDate(b) - getDate(a);
      }
    });

    const frag = document.createDocumentFragment();
    for(const r of visible) frag.appendChild(r);
    for(const r of rows){
      if(r.classList.contains('hidden')) frag.appendChild(r);
    }
    tbody.appendChild(frag);

    const n = visible.length;
    if(countBadge) countBadge.textContent = n.toString();

    if(emptyRow){
      if(n === 0) emptyRow.classList.remove('hidden');
      else emptyRow.classList.add('hidden');
    }
  }

  function initFilterSection(sectionId){
    const root = document.getElementById(sectionId);
    if(!root) return;

    const inputs = root.querySelectorAll('[data-filter]');
    inputs.forEach(i => {
      i.addEventListener('input', () => applyFilters(sectionId));
      i.addEventListener('change', () => applyFilters(sectionId));
    });

    const resetBtn = root.querySelector('[data-action="reset"]');
    if(resetBtn){
      resetBtn.addEventListener('click', () => {
        const q = root.querySelector('[data-filter="q"]');
        const st = root.querySelector('[data-filter="status"]');
        const sort = root.querySelector('[data-filter="sort"]');
        if(q) q.value = '';
        if(st) st.value = 'all';
        if(sort) sort.value = 'date_desc';
        applyFilters(sectionId);
      });
    }

    applyFilters(sectionId);
  }

  // ✅ NEW: validation note admin selon action
  function validateAdminNote(form, opts = {}){
    try{
      const required = !!opts.required;
      if(!required) return true;

      const hidden = form.querySelector('input[name="admin_note"]');
      const note = (hidden?.value ?? '').trim();

      if(note !== '') return true;

      const td = form.closest('td');
      const preview = td?.querySelector('input[name="__note_preview"]');

      alert(opts.message || 'Note admin obligatoire pour cette action.');
      if(preview){
        preview.focus();
        preview.classList.add('ring-2','ring-rose-400/60');
        setTimeout(() => preview.classList.remove('ring-2','ring-rose-400/60'), 1200);
      }
      return false;
    } catch(e){
      // en cas d'erreur JS, on ne bloque pas (mais normalement ça ne doit pas arriver)
      return true;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initFilterSection('pendingSection');
    initFilterSection('recentSection');
  });
</script>

<div class="space-y-8">

  {{-- Header --}}
  <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
          <div class="text-sm vy-muted">Admin</div>
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">
            Retraits (Points → PayPal)
          </h1>
          <p class="vy-muted mt-1 text-sm">
              À traiter = <span class="text-white font-semibold">Pending + Hold</span>.
              Règles : cooldown 24h, 2/jour, 50€/jour. Risk HIGH ⇒ <span class="text-white font-semibold">HOLD</span>.
          </p>
      </div>

      <div class="flex items-center gap-3">
          <a href="{{ route('dashboard') }}" class="vy-btn">Retour dashboard</a>
          <a href="{{ route('withdraw') }}" class="vy-btn">Page retrait</a>
      </div>
  </div>

  {{-- Quick stats --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
    <div class="vy-card p-4">
      <div class="vy-label">À traiter (total)</div>
      <div class="mt-1 text-lg font-semibold text-white">{{ number_format($countPending, 0, ',', ' ') }}</div>
      <div class="mt-1 text-xs vy-muted">
        Total: <span class="text-white font-semibold">{{ $fmtPts($sumPendingPts) }}</span> (≈ {{ $fmtEur($sumPendingPts) }})
      </div>
    </div>

    <div class="vy-card p-4">
      <div class="vy-label">Récents (total)</div>
      <div class="mt-1 text-lg font-semibold text-white">{{ number_format($countRecent, 0, ',', ' ') }}</div>
      <div class="mt-1 text-xs vy-muted">
        Total: <span class="text-white font-semibold">{{ $fmtPts($sumRecentPts) }}</span> (≈ {{ $fmtEur($sumRecentPts) }})
      </div>
    </div>

    <div class="vy-card p-4">
      <div class="vy-label">Règles payout</div>
      <div class="mt-1 text-sm text-white font-semibold">Cooldown 24h • 2/jour • 50€/jour</div>
      <div class="mt-1 text-xs vy-muted">V1 : PayPal</div>
    </div>

    <div class="vy-card p-4">
      <div class="vy-label">Hold</div>
      <div class="mt-1 text-sm text-white font-semibold">Risk HIGH ⇒ HOLD</div>
      <div class="mt-1 text-xs vy-muted">Release obligatoire avant paiement</div>
    </div>
  </div>

  {{-- À traiter --}}
  <div id="pendingSection" class="vy-card overflow-hidden">
      <div class="p-6 vy-card-h">
          <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
              <h2 class="text-xl font-semibold text-white">À traiter</h2>
              <p class="text-sm vy-muted mt-1">
                Demandes en <span class="text-white font-semibold">Pending</span> + <span class="text-white font-semibold">Hold</span>.
              </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
              <input data-filter="q" type="text" placeholder="Rechercher (email, PayPal, ref, IP, id...)"
                     class="vy-field sm:w-80" />

              <select data-filter="status" class="vy-select sm:w-44">
                <option value="all">Tous statuts</option>
                <option value="hold">Hold</option>
                <option value="pending">En attente</option>
                <option value="paid">Payé</option>
                <option value="rejected">Rejeté</option>
              </select>

              <select data-filter="sort" class="vy-select sm:w-44">
                <option value="date_desc">Date ↓</option>
                <option value="date_asc">Date ↑</option>
                <option value="amount_desc">Montant ↓</option>
                <option value="amount_asc">Montant ↑</option>
                <option value="status_asc">Statut ↑</option>
                <option value="status_desc">Statut ↓</option>
              </select>

              <button type="button" data-action="reset" class="vy-btn">Reset</button>

              <div class="vy-pill">
                <span data-count="visible">{{ $countPending }}</span> visibles
              </div>
            </div>
          </div>
      </div>

      <div class="overflow-x-auto">
          <table class="min-w-full text-sm vy-table">
              <thead>
                  <tr>
                      <th class="text-left px-6 py-3 font-medium">Date</th>
                      <th class="text-left px-6 py-3 font-medium">Utilisateur</th>
                      <th class="text-left px-6 py-3 font-medium">PayPal</th>
                      <th class="text-left px-6 py-3 font-medium">Montant</th>
                      <th class="text-left px-6 py-3 font-medium">Risk</th>
                      <th class="text-left px-6 py-3 font-medium">Statut</th>
                      <th class="text-right px-6 py-3 font-medium">Actions</th>
                  </tr>
              </thead>

              <tbody data-tbody="rows" class="divide-y divide-white/5">
                  @forelse($pending as $w)
                      @php
                          $dt = $whenFromTx($w);
                          $ts = optional($dt)->timestamp ?? 0;

                          $status = $w->status ?? 'pending';
                          $amountPts = (int) ($w->amount_cents ?? 0);

                          $paypal = $w->paypal_email ?? '—';
                          $ref = $w->reference ?? '—';

                          $ip = (string) ($w->ip ?? '—');
                          $ua = (string) ($w->user_agent ?? '—');
                          $idem = (string) ($w->idempotency_key ?? '—');

                          $riskScore = (int) ($w->risk_score ?? 0);
                          $riskLevel = (string) ($w->risk_level ?? 'low');
                          $riskFlags = (array) ($w->risk_flags ?? []);

                          $needsAdminNote = (bool) ($w->needs_admin_note ?? false);

                          $uName = (string) ($w->user?->name ?? '');
                          $uEmail = (string) ($w->user?->email ?? '');

                          $search = implode(' | ', array_filter([
                            'id:' . ($w->id ?? ''),
                            $uName,
                            $uEmail,
                            $paypal,
                            $ref,
                            $ip,
                            $idem,
                            $status,
                            'risk:' . $riskLevel . ':' . $riskScore,
                          ]));
                      @endphp

                      <tr data-row="tx"
                          data-status="{{ $status }}"
                          data-amount="{{ $amountPts }}"
                          data-ts="{{ $ts }}"
                          data-search="{{ e($search) }}"
                          class="vy-row align-top">
                          <td class="px-6 py-4 vy-muted whitespace-nowrap">
                              <div class="text-white/90">{{ optional($dt)->format('d/m/Y H:i') }}</div>
                              <div class="text-xs mt-1">
                                #{{ $w->id ?? '—' }}
                                <button type="button" onclick="copyText('{{ (string)($w->id ?? '') }}')" class="ml-2 vy-mini-btn">
                                  Copier ID
                                </button>
                              </div>
                          </td>

                          <td class="px-6 py-4">
                              <div class="text-white font-semibold">{{ $w->user?->name ?? '—' }}</div>
                              <div class="text-xs vy-muted">{{ $w->user?->email ?? '—' }}</div>
                          </td>

                          <td class="px-6 py-4 vy-muted">
                              <div class="flex items-center gap-2">
                                <span class="truncate max-w-[240px] text-white/90">{{ $paypal }}</span>
                                <button type="button" onclick="copyText('{{ addslashes((string)$paypal) }}')" class="vy-mini-btn">
                                  Copier
                                </button>
                              </div>
                              <div class="text-xs mt-1">
                                Ref: <span class="text-white/90">{{ $ref }}</span>
                                <button type="button" onclick="copyText('{{ addslashes((string)$ref) }}')" class="ml-2 vy-mini-btn">
                                  Copier
                                </button>
                              </div>
                          </td>

                          <td class="px-6 py-4 text-white font-semibold whitespace-nowrap">
                              {{ $fmtPts($amountPts) }}
                              <div class="text-xs font-normal vy-muted">≈ {{ $fmtEur($amountPts) }}</div>
                          </td>

                          <td class="px-6 py-4">
                              <div class="inline-flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $riskBadge($riskLevel) }}">
                                  {{ $riskLabel($riskLevel) }} • {{ $riskScore }}
                                </span>

                                @if($needsAdminNote)
                                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white/10 text-white/90 border border-white/10 text-xs">
                                    Note requise
                                  </span>
                                @endif
                              </div>

                              @if(!empty($riskFlags))
                                <div class="mt-2 text-xs vy-muted space-y-1">
                                  @foreach($riskFlags as $f)
                                    <div>• {{ $f }}</div>
                                  @endforeach
                                </div>
                              @endif
                          </td>

                          <td class="px-6 py-4">
                              <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badgeStatus($status) }}">
                                  {{ $labelStatus($status) }}
                              </span>
                          </td>

                          <td class="px-6 py-4">
                              <div class="flex flex-col items-end gap-2">

                                  {{-- ✅ NOTE: champ unique qui alimente les inputs hidden des forms + validation côté client --}}
                                  <input
                                    type="text"
                                    name="__note_preview"
                                    value=""
                                    placeholder="{{ $needsAdminNote ? 'Note admin (obligatoire si risk élevé)' : 'Note admin (optionnelle)' }}"
                                    class="vy-field w-72 max-w-full"
                                    oninput="
                                      const wrap=this.closest('td');
                                      if(!wrap) return;
                                      wrap.querySelectorAll('input[name=admin_note]').forEach(i=>i.value=this.value);
                                    "
                                  />

                                  <div class="flex justify-end gap-2 flex-wrap">
                                      @if($status === 'hold')
                                          <form method="POST"
                                                action="{{ route('admin.withdrawals.release', $w) }}"
                                                onsubmit="return validateAdminNote(this, { required: true, message: 'Note admin obligatoire pour libérer un retrait en HOLD.' }) && confirm('Lever le HOLD et repasser ce retrait en PENDING ?');">
                                              @csrf
                                              <input type="hidden" name="admin_note" value="">
                                              <button type="submit" class="vy-btn vy-btn-indigo">Release hold</button>
                                          </form>
                                      @endif

                                      @if($status === 'pending')
                                          <form method="POST"
                                                action="{{ route('admin.withdrawals.markPaid', $w) }}"
                                                onsubmit="return validateAdminNote(this, { required: {{ $needsAdminNote ? 'true' : 'false' }}, message: 'Note admin obligatoire pour un retrait à risque élevé.' }) && confirm('Marquer ce retrait comme PAYÉ ?');">
                                              @csrf
                                              <input type="hidden" name="admin_note" value="">
                                              <button type="submit" class="vy-btn vy-btn-emerald">Marquer payé</button>
                                          </form>
                                      @endif

                                      <form method="POST"
                                            action="{{ route('admin.withdrawals.reject', $w) }}"
                                            onsubmit="return validateAdminNote(this, { required: {{ $needsAdminNote ? 'true' : 'false' }}, message: 'Note admin obligatoire pour un retrait à risque élevé.' }) && confirm('Rejeter ce retrait et RECRÉDITER les points ?');">
                                          @csrf
                                          <input type="hidden" name="admin_note" value="">
                                          <button type="submit" class="vy-btn vy-btn-rose">Rejeter</button>
                                      </form>
                                  </div>

                                  <details class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/25 p-3">
                                      <summary class="cursor-pointer text-xs font-semibold text-white/85">
                                          Détails (IP / UA / idempotency)
                                      </summary>

                                      <div class="mt-3 space-y-2 text-xs text-white/85">
                                          <div class="flex items-center justify-between gap-2">
                                              <span class="vy-muted">IP</span>
                                              <div class="flex items-center gap-2">
                                                  <span class="text-white/90 font-medium truncate max-w-[260px]">{{ $ip }}</span>
                                                  <button type="button" onclick="copyText('{{ addslashes((string)$ip) }}')" class="vy-mini-btn">
                                                      Copier
                                                  </button>
                                              </div>
                                          </div>

                                          <div class="flex items-center justify-between gap-2">
                                              <span class="vy-muted">Idempotency</span>
                                              <div class="flex items-center gap-2">
                                                  <span class="text-white/90 font-medium truncate max-w-[260px]">{{ $idem }}</span>
                                                  <button type="button" onclick="copyText('{{ addslashes((string)$idem) }}')" class="vy-mini-btn">
                                                      Copier
                                                  </button>
                                              </div>
                                          </div>

                                          <div>
                                              <div class="vy-muted">User-Agent</div>
                                              <div class="mt-1 text-white/90 font-medium break-words">{{ $ua }}</div>
                                          </div>
                                      </div>
                                  </details>
                              </div>
                          </td>
                      </tr>
                  @empty
                      <tr data-empty="row">
                          <td colspan="7" class="px-6 py-10 text-center vy-muted">
                              Aucun retrait à traiter.
                          </td>
                      </tr>
                  @endforelse

                  <tr data-empty="row" class="hidden">
                    <td colspan="7" class="px-6 py-10 text-center vy-muted">
                      Aucun résultat avec ces filtres.
                    </td>
                  </tr>
              </tbody>
          </table>
      </div>

      <div class="p-6 text-xs vy-muted border-t border-white/10">
          HOLD = review manuelle. “Rejeter” recrédite automatiquement l’utilisateur et enregistre une transaction d’audit.
      </div>
  </div>

  {{-- Recent --}}
  <div id="recentSection" class="vy-card overflow-hidden">
      <div class="p-6 vy-card-h">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
              <h2 class="text-xl font-semibold text-white">Récents</h2>
              <p class="text-sm vy-muted mt-1">Les dernières demandes (tous statuts).</p>
          </div>

          <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
            <input data-filter="q" type="text" placeholder="Rechercher (email, PayPal, ref, IP, id...)" class="vy-field sm:w-80" />

            <select data-filter="status" class="vy-select sm:w-44">
              <option value="all">Tous statuts</option>
              <option value="hold">Hold</option>
              <option value="pending">En attente</option>
              <option value="paid">Payé</option>
              <option value="rejected">Rejeté</option>
            </select>

            <select data-filter="sort" class="vy-select sm:w-44">
              <option value="date_desc">Date ↓</option>
              <option value="date_asc">Date ↑</option>
              <option value="amount_desc">Montant ↓</option>
              <option value="amount_asc">Montant ↑</option>
              <option value="status_asc">Statut ↑</option>
              <option value="status_desc">Statut ↓</option>
            </select>

            <button type="button" data-action="reset" class="vy-btn">Reset</button>

            <div class="vy-pill">
              <span data-count="visible">{{ $countRecent }}</span> visibles
            </div>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
          <table class="min-w-full text-sm vy-table">
              <thead>
                  <tr>
                      <th class="text-left px-6 py-3 font-medium">Date</th>
                      <th class="text-left px-6 py-3 font-medium">Utilisateur</th>
                      <th class="text-left px-6 py-3 font-medium">PayPal</th>
                      <th class="text-left px-6 py-3 font-medium">Montant</th>
                      <th class="text-left px-6 py-3 font-medium">Risk</th>
                      <th class="text-left px-6 py-3 font-medium">Statut</th>
                      <th class="text-left px-6 py-3 font-medium">Audit</th>
                  </tr>
              </thead>

              <tbody data-tbody="rows" class="divide-y divide-white/5">
                  @forelse($recent as $w)
                      @php
                          $dt = $whenFromTx($w);
                          $ts = optional($dt)->timestamp ?? 0;

                          $status = $w->status ?? 'pending';
                          $amountPts = (int) ($w->amount_cents ?? 0);
                          $paypal = $w->paypal_email ?? '—';
                          $ref = $w->reference ?? '—';

                          $riskScore = (int) ($w->risk_score ?? 0);
                          $riskLevel = (string) ($w->risk_level ?? 'low');

                          $meta = is_array($w->meta ?? null) ? $w->meta : (is_object($w->meta ?? null) ? (array)$w->meta : []);

                          $paidAt = (string) ($meta['paid_at'] ?? '');
                          $rejectedAt = (string) ($meta['rejected_at'] ?? '');
                          $releasedAt = (string) ($meta['released_at'] ?? '');
                          $adminNote = (string) ($meta['admin_note'] ?? '');
                          $adminId = (string) ($meta['admin_user_id'] ?? '');

                          $ip = (string) ($w->ip ?? '');
                          $idem = (string) ($w->idempotency_key ?? '');

                          $uName = (string) ($w->user?->name ?? '');
                          $uEmail = (string) ($w->user?->email ?? '');

                          $search = implode(' | ', array_filter([
                            'id:' . ($w->id ?? ''),
                            $uName,
                            $uEmail,
                            $paypal,
                            $ref,
                            $ip,
                            $idem,
                            $status,
                            'risk:' . $riskLevel . ':' . $riskScore,
                            $adminNote,
                          ]));
                      @endphp

                      <tr data-row="tx"
                          data-status="{{ $status }}"
                          data-amount="{{ $amountPts }}"
                          data-ts="{{ $ts }}"
                          data-search="{{ e($search) }}"
                          class="vy-row">
                          <td class="px-6 py-4 vy-muted whitespace-nowrap">
                              <div class="text-white/90">{{ optional($dt)->format('d/m/Y H:i') }}</div>
                              <div class="text-xs mt-1">#{{ $w->id ?? '—' }}</div>
                          </td>

                          <td class="px-6 py-4">
                              <div class="text-white font-semibold">{{ $w->user?->name ?? '—' }}</div>
                              <div class="text-xs vy-muted">{{ $w->user?->email ?? '—' }}</div>
                          </td>

                          <td class="px-6 py-4 vy-muted">
                              <div class="flex items-center gap-2">
                                <span class="truncate max-w-[260px] text-white/90">{{ $paypal }}</span>
                                <button type="button" onclick="copyText('{{ addslashes((string)$paypal) }}')" class="vy-mini-btn">
                                  Copier
                                </button>
                              </div>
                              <div class="text-xs mt-1">
                                Ref: <span class="text-white/90">{{ $ref }}</span>
                              </div>
                          </td>

                          <td class="px-6 py-4 text-white font-semibold whitespace-nowrap">
                              {{ $fmtPts($amountPts) }}
                              <div class="text-xs font-normal vy-muted">≈ {{ $fmtEur($amountPts) }}</div>
                          </td>

                          <td class="px-6 py-4">
                              <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $riskBadge($riskLevel) }}">
                                  {{ $riskLabel($riskLevel) }} • {{ $riskScore }}
                              </span>
                          </td>

                          <td class="px-6 py-4">
                              <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badgeStatus($status) }}">
                                  {{ $labelStatus($status) }}
                              </span>
                          </td>

                          <td class="px-6 py-4 text-xs text-white/85">
                              @if($status === 'paid' && $paidAt)
                                  <div><span class="vy-muted">Payé:</span> <span class="text-white/90 font-medium">{{ $paidAt }}</span></div>
                              @elseif($status === 'rejected' && $rejectedAt)
                                  <div><span class="vy-muted">Rejeté:</span> <span class="text-white/90 font-medium">{{ $rejectedAt }}</span></div>
                              @elseif($status === 'pending' && $releasedAt)
                                  <div><span class="vy-muted">Release:</span> <span class="text-white/90 font-medium">{{ $releasedAt }}</span></div>
                              @endif

                              @if($adminId)
                                  <div class="mt-1"><span class="vy-muted">Admin ID:</span> <span class="text-white/90 font-medium">#{{ $adminId }}</span></div>
                              @endif

                              @if($adminNote)
                                  <div class="mt-1 vy-muted">Note:</div>
                                  <div class="text-white/90 break-words">{{ $adminNote }}</div>
                              @endif
                          </td>
                      </tr>
                  @empty
                      <tr data-empty="row">
                          <td colspan="7" class="px-6 py-10 text-center vy-muted">
                              Aucun retrait.
                          </td>
                      </tr>
                  @endforelse

                  <tr data-empty="row" class="hidden">
                    <td colspan="7" class="px-6 py-10 text-center vy-muted">
                      Aucun résultat avec ces filtres.
                    </td>
                  </tr>
              </tbody>
          </table>
      </div>

      <div class="p-6 text-xs vy-muted border-t border-white/10">
          Conversion interne : <span class="text-white font-semibold">1 pt = 0,01€</span>. Les montants stockés sont en points.
      </div>
  </div>

</div>
@endsection
