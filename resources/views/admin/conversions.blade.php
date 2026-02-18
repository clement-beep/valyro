{{-- resources/views/admin/conversions.blade.php --}}
@extends('layouts.app')

@section('content')
@php
  // Compat si contrôleur renvoie $events / $rows
  $conversions = $conversions ?? $events ?? $rows ?? null;
  if(!$conversions){
      $conversions = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
  }

  // Helpers format
  $fmtPts = fn(int $pts) => number_format($pts, 0, ',', ' ') . ' pts';
  $fmtEur = fn(int $pts) => number_format($pts / 100, 2, ',', ' ') . ' €'; // 1pt = 0.01€

  // Filtres (UI)
  $q = request('q', '');
  $st = request('status', 'all');
  $nw = request('network', 'all');
  $sort = request('sort', 'received_desc');

  // Labels / badges
  $labelStatus = function (?string $s) {
      $s = strtolower((string)$s);
      return match($s){
        'confirmed' => 'Confirmé',
        'pending' => 'En attente',
        'rejected' => 'Rejeté',
        default => '—',
      };
  };

  $badgeStatus = function (?string $s) {
      $s = strtolower((string)$s);
      return match($s){
        'confirmed' => 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/20',
        'pending' => 'bg-amber-500/15 text-amber-200 border border-amber-500/20',
        'rejected' => 'bg-rose-500/15 text-rose-200 border border-rose-500/20',
        default => 'bg-white/10 text-slate-200 border border-white/10',
      };
  };

  // Réseaux dispo (si contrôleur ne le fournit pas)
  $networks = $networks ?? ['bitlabs'];

  // Simulateur (optionnel)
  $simUrl = null;
  try { $simUrl = route('admin.postback.simulate'); } catch (\Throwable $e) { $simUrl = null; }

  // Replay postback (admin only UI) — aucune route admin nécessaire
  // On génère des URLs / commandes curl à rejouer pour debug rapide.
  $postbackUrl = url('/postback');
  $postbackToken = (string) config('valyro.postback.token', '');
  $buildReplayUrl = function(string $subid, string $externalId, string $status, int $payoutPts) use ($postbackUrl, $postbackToken) {
      $payout = number_format($payoutPts / 100, 2, '.', ''); // payout en "€" dans tes tests
      $qs = http_build_query([
          'token'  => $postbackToken,
          'subid'  => $subid,
          'status' => $status,
          'payout' => $payout,
          'txnid'  => $externalId,
      ]);
      return $postbackUrl . '?' . $qs;
  };
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
.vy-field, .vy-select{
  width: 100%;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(2,6,23,.28);
  padding: 10px 12px;
  color: var(--vy-ink) !important;
  font-size: 14px;
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
.vy-btn:disabled{ opacity:.45; cursor:not-allowed; }
.vy-btn-indigo{ border-color: rgba(99,102,241,.28); background: rgba(99,102,241,.14); color: rgba(199,210,254,.98); }

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
.vy-toast{
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,.10);
  background: rgba(2,6,23,.35);
  padding: 12px 14px;
}
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
    t.focus(); t.select();
    try { document.execCommand("copy"); } catch(e) {}
    document.body.removeChild(t);
  }

  function openNewTab(url){
    if(!url) return;
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  async function runPostbackTest(url, csrf){
    const btn = document.getElementById('btnPostbackTest');
    const out = document.getElementById('postbackTestOut');

    const setOut = (html) => { if(out) out.innerHTML = html; };

    if(!url){
      setOut(`<div class="vy-toast"><div class="font-semibold text-rose-200">Route simulateur introuvable</div><div class="mt-1 text-xs text-white/80">Vérifie route('admin.postback.simulate')</div></div>`);
      return;
    }

    if(btn){ btn.disabled = true; btn.textContent = 'Test en cours…'; }
    setOut(`<div class="vy-toast"><div class="text-xs text-white/75">Envoi du test…</div></div>`);

    try{
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          status: 'confirmed',
          payout_points: 150 // ✅ points
        })
      });

      const data = await res.json().catch(() => null);

      if(!data){
        const txt = await res.text().catch(()=> '');
        setOut(`
          <div class="vy-toast">
            <div class="font-semibold text-rose-200">Erreur simulateur</div>
            <div class="mt-2 text-xs text-white/80 break-words">Réponse non-JSON (HTTP ${res.status})</div>
            <pre class="mt-2 whitespace-pre-wrap break-words text-white/90 bg-black/30 border border-white/10 rounded-xl p-3">${(txt||'—').slice(0, 400)}</pre>
          </div>
        `);
        return;
      }

      if(!res.ok || !data.ok){
        setOut(`
          <div class="vy-toast">
            <div class="font-semibold text-rose-200">Erreur simulateur (${res.status})</div>
            <div class="mt-2 text-xs text-white/80 break-words">${data.message || data.hint || 'Erreur'}</div>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-white/85">
              <div><span class="text-white/60">postback_http:</span> <span class="font-semibold">${data.postback_http ?? '—'}</span></div>
              <div><span class="text-white/60">postback_body:</span> <span class="font-semibold">${(data.postback_body ?? '—')}</span></div>
              <div><span class="text-white/60">subid:</span> <span class="font-semibold">${data.subid || '—'}</span></div>
              <div><span class="text-white/60">external_id:</span> <span class="font-semibold">${data.external_id || '—'}</span></div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
              <button type="button" class="vy-mini-btn" onclick="copyText('${data.subid || ''}')">Copier subid</button>
              <button type="button" class="vy-mini-btn" onclick="copyText('${data.external_id || ''}')">Copier external_id</button>
            </div>
          </div>
        `);
        return;
      }

      // ✅ OK réel (postback 200 + conversion trouvée)
      setOut(`
        <div class="vy-toast">
          <div class="font-semibold text-emerald-200">OK — Conversion créée</div>

          <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-white/85">
            <div><span class="text-white/60">subid:</span> <span class="font-semibold">${data.subid || '—'}</span></div>
            <div><span class="text-white/60">external_id:</span> <span class="font-semibold">${data.external_id || '—'}</span></div>
            <div><span class="text-white/60">payout:</span> <span class="font-semibold">${(data.payout_points ?? 0)} pts</span></div>
            <div><span class="text-white/60">status:</span> <span class="font-semibold">${data.status || '—'}</span></div>
            <div><span class="text-white/60">postback_http:</span> <span class="font-semibold">${data.postback_http ?? '—'}</span></div>
            <div><span class="text-white/60">conv_id:</span> <span class="font-semibold">${data.conversion?.id ?? '—'}</span></div>
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" class="vy-mini-btn" onclick="copyText('${data.subid || ''}')">Copier subid</button>
            <button type="button" class="vy-mini-btn" onclick="copyText('${data.external_id || ''}')">Copier external_id</button>
            <button type="button" class="vy-mini-btn" onclick="location.reload()">Rafraîchir la liste</button>
          </div>

          ${data.balance ? `<div class="mt-3 text-xs text-white/75">Solde: <span class="text-white/90 font-semibold">${data.balance.balance_pts}</span> pts • Pending: <span class="text-white/90 font-semibold">${data.balance.pending_pts}</span> pts</div>` : ``}
        </div>
      `);

    } catch(e){
      setOut(`
        <div class="vy-toast">
          <div class="font-semibold text-rose-200">Erreur JS</div>
          <div class="mt-2 text-xs text-white/80 break-words">${(e && e.message) ? e.message : e}</div>
        </div>
      `);
    } finally {
      if(btn){ btn.disabled = false; btn.textContent = 'Test postback'; }
    }
  }
</script>

<div class="space-y-8">
  <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <div class="text-sm vy-muted">Admin</div>
      <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">Conversions (Postbacks)</h1>
      <p class="vy-muted mt-1 text-sm">
        Historique des conversions reçues (<span class="text-white font-semibold">pending/confirmed/rejected</span>) + métadonnées.
      </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <a href="{{ route('dashboard') }}" class="vy-btn">Retour dashboard</a>
      <a href="{{ route('admin.users') }}" class="vy-btn">Liste utilisateurs</a>

      <button
        id="btnPostbackTest"
        type="button"
        class="vy-btn vy-btn-indigo"
        @disabled(!$simUrl)
        onclick="runPostbackTest('{{ $simUrl ?? '' }}','{{ csrf_token() }}')">
        Test postback
      </button>
    </div>
  </div>

  <div id="postbackTestOut"></div>

  <div class="vy-card overflow-hidden">
    <div class="p-6 vy-card-h">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-white">Filtres</h2>
          <p class="text-sm vy-muted mt-1">Recherche par subid, external_id, email, network, statut…</p>
        </div>

        <form method="GET" class="flex flex-col sm:flex-row gap-2 sm:items-center w-full md:w-auto">
          <input name="q" value="{{ $q }}" class="vy-field sm:w-80" placeholder="Rechercher… (subid, external_id, email…)" />

          <select name="status" class="vy-select sm:w-44">
            <option value="all" @selected($st==='all')>Tous statuts</option>
            <option value="confirmed" @selected($st==='confirmed')>Confirmé</option>
            <option value="pending" @selected($st==='pending')>En attente</option>
            <option value="rejected" @selected($st==='rejected')>Rejeté</option>
          </select>

          <select name="network" class="vy-select sm:w-44">
            <option value="all" @selected($nw==='all')>Tous réseaux</option>
            @foreach($networks as $n)
              <option value="{{ $n }}" @selected($nw===$n)>{{ $n }}</option>
            @endforeach
          </select>

          <select name="sort" class="vy-select sm:w-44">
            <option value="received_desc" @selected($sort==='received_desc')>Date ↓</option>
            <option value="received_asc" @selected($sort==='received_asc')>Date ↑</option>
            <option value="payout_desc" @selected($sort==='payout_desc')>Payout ↓</option>
            <option value="payout_asc" @selected($sort==='payout_asc')>Payout ↑</option>
          </select>

          <button class="vy-btn" type="submit">Appliquer</button>
          <a class="vy-btn" href="{{ route('admin.conversions') }}">Reset</a>

          <div class="vy-pill">
            {{ number_format((int)($conversions->total() ?? 0), 0, ',', ' ') }} total
          </div>
        </form>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm vy-table">
        <thead>
          <tr>
            <th class="text-left px-6 py-3 font-medium">Dates</th>
            <th class="text-left px-6 py-3 font-medium">Statut</th>
            <th class="text-left px-6 py-3 font-medium">Network</th>
            <th class="text-left px-6 py-3 font-medium">External ID</th>
            <th class="text-left px-6 py-3 font-medium">SubID</th>
            <th class="text-left px-6 py-3 font-medium">User</th>
            <th class="text-left px-6 py-3 font-medium">Offer</th>
            <th class="text-left px-6 py-3 font-medium">Points</th>
            <th class="text-left px-6 py-3 font-medium">Détails</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-white/5">
          @forelse($conversions as $c)
            @php
              // Dates (tes colonnes réelles)
              $receivedAt  = $c->received_at ?? $c->created_at ?? null;
              $firstSeenAt = $c->first_seen_at ?? null;
              $lastSeenAt  = $c->last_seen_at ?? null;

              // Champs essentiels
              $status = strtolower((string)($c->status ?? ''));
              $payoutPts = (int)($c->payout_points ?? 0);
              $pendingPts = (int)($c->pending_points ?? 0);
              $confirmedPts = (int)($c->confirmed_points ?? 0);

              $network = (string)($c->network ?? '—');
              $external = (string)($c->external_id ?? '—');
              $subid = (string)($c->subid ?? '—');

              // User (si relation chargée)
              $uEmail = (string)($c->user?->email ?? '—');
              $uName  = (string)($c->user?->name ?? '—');
              $uId    = (string)($c->user_id ?? '—');

              // Offer / Click
              $offerTitle = (string)($c->offer?->title ?? '—');
              $clickId = $c->offer_click_id ?? '—';

              // Détails (colonnes réelles)
              $ip = (string)($c->last_ip ?? $c->ip ?? '');
              $ua = (string)($c->user_agent ?? '');

              // Payload : on privilégie last_payload, sinon raw
              $raw = $c->last_payload ?? $c->raw ?? null;

              // Cast safety si raw stocké en string JSON
              if(is_string($raw)){
                $decoded = json_decode($raw, true);
                if(json_last_error() === JSON_ERROR_NONE) $raw = $decoded;
              }

              $hasAnyDetails = (($ip !== '') || ($ua !== '') || (!empty($raw)));

              // Replay URLs (admin debug)
              $replayPendingUrl   = $buildReplayUrl($subid, $external, 'pending', $payoutPts);
              $replayConfirmedUrl = $buildReplayUrl($subid, $external, 'confirmed', $payoutPts);
              $replayRejectedUrl  = $buildReplayUrl($subid, $external, 'rejected', $payoutPts);

              $curlPending   = "curl -i " . escapeshellarg($replayPendingUrl);
              $curlConfirmed = "curl -i " . escapeshellarg($replayConfirmedUrl);
              $curlRejected  = "curl -i " . escapeshellarg($replayRejectedUrl);
            @endphp

            <tr class="vy-row align-top">
              <td class="px-6 py-4 vy-muted whitespace-nowrap">
                <div class="text-white/90">{{ optional($receivedAt)->format('d/m/Y H:i') ?? '—' }}</div>
                <div class="text-xs mt-1">
                  <span class="text-white/70">#{{ $c->id ?? '—' }}</span>
                  @if($lastSeenAt)
                    <span class="mx-2 text-white/20">•</span>
                    <span class="text-white/60">last:</span> <span class="text-white/75">{{ optional($lastSeenAt)->format('d/m H:i') }}</span>
                  @endif
                  @if($firstSeenAt)
                    <span class="mx-2 text-white/20">•</span>
                    <span class="text-white/60">first:</span> <span class="text-white/75">{{ optional($firstSeenAt)->format('d/m H:i') }}</span>
                  @endif
                </div>
              </td>

              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $badgeStatus($status) }}">
                  {{ $labelStatus($status) }}
                </span>
              </td>

              <td class="px-6 py-4 text-white/85">{{ $network }}</td>

              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="text-white/90 font-semibold truncate max-w-[220px]">{{ $external }}</span>
                  <button type="button" class="vy-mini-btn" onclick="copyText(@js($external))">Copier</button>
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                  <button type="button" class="vy-mini-btn" onclick="copyText(@js($curlConfirmed))">Copier CURL confirmed</button>
                </div>
              </td>

              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="text-white/90 font-semibold truncate max-w-[240px]">{{ $subid }}</span>
                  <button type="button" class="vy-mini-btn" onclick="copyText(@js($subid))">Copier</button>
                </div>
                <div class="text-xs vy-muted mt-2">Click: {{ $clickId }}</div>
              </td>

              <td class="px-6 py-4">
                <div class="text-white font-semibold">{{ $uName }}</div>
                <div class="text-xs vy-muted">{{ $uEmail }}</div>
                <div class="text-xs mt-1 text-white/70">User ID: <span class="text-white/85 font-semibold">{{ $uId }}</span></div>
              </td>

              <td class="px-6 py-4 text-white/85">
                <div class="text-xs vy-muted">Offer:</div>
                <div class="text-white/90 font-medium">{{ $offerTitle }}</div>
              </td>

              <td class="px-6 py-4 text-white font-semibold whitespace-nowrap">
                {{ $fmtPts($payoutPts) }}
                <div class="text-xs font-normal vy-muted">≈ {{ $fmtEur($payoutPts) }}</div>

                <div class="mt-2 text-xs text-white/75">
                  Pending: <span class="text-white/90 font-semibold">{{ (int)$pendingPts }}</span>
                  <span class="mx-1 text-white/20">•</span>
                  Confirmed: <span class="text-white/90 font-semibold">{{ (int)$confirmedPts }}</span>
                </div>
              </td>

              <td class="px-6 py-4">
                <details class="rounded-2xl border border-white/10 bg-slate-950/25 p-3">
                  <summary class="cursor-pointer text-xs font-semibold text-white/85">IP / UA / Payload + Actions</summary>

                  @if(!$hasAnyDetails)
                    <div class="mt-3 text-xs text-white/70">
                      Aucune donnée (normal si ancien enregistrement, ou si le réseau n’envoie pas les champs).
                    </div>
                  @else
                    <div class="mt-3 space-y-3 text-xs text-white/85">
                      <div class="flex items-center justify-between gap-2">
                        <span class="vy-muted">IP</span>
                        <div class="flex items-center gap-2">
                          <span class="text-white/90 font-medium truncate max-w-[260px]">{{ $ip !== '' ? $ip : '—' }}</span>
                          @if($ip !== '')
                            <button type="button" class="vy-mini-btn" onclick="copyText(@js($ip))">Copier</button>
                          @endif
                        </div>
                      </div>

                      <div>
                        <div class="vy-muted">User-Agent</div>
                        <div class="mt-1 text-white/90 font-medium break-words">{{ $ua !== '' ? $ua : '—' }}</div>
                      </div>

                      <div>
                        <div class="vy-muted">Payload (last_payload puis raw)</div>
                        @if(!empty($raw))
                          <pre class="mt-1 whitespace-pre-wrap break-words text-white/90 bg-black/30 border border-white/10 rounded-xl p-3">{{ json_encode($raw, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                          <div class="mt-1 text-white/70">—</div>
                        @endif
                      </div>
                    </div>
                  @endif

                  {{-- ✅ Boutons utiles (sans changer ton backend) : rejouer un postback en 1 clic / copier le curl --}}
                  <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <button type="button" class="vy-mini-btn" onclick="openNewTab(@js($replayPendingUrl))">Rejouer pending</button>
                    <button type="button" class="vy-mini-btn" onclick="openNewTab(@js($replayConfirmedUrl))">Rejouer confirmed</button>
                    <button type="button" class="vy-mini-btn" onclick="openNewTab(@js($replayRejectedUrl))">Rejouer rejected</button>
                  </div>

                  <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <button type="button" class="vy-mini-btn" onclick="copyText(@js($curlPending))">Copier CURL pending</button>
                    <button type="button" class="vy-mini-btn" onclick="copyText(@js($curlConfirmed))">Copier CURL confirmed</button>
                    <button type="button" class="vy-mini-btn" onclick="copyText(@js($curlRejected))">Copier CURL rejected</button>
                  </div>

                  @if(empty($postbackToken))
                    <div class="mt-3 text-xs text-amber-200">
                      ⚠️ Token postback vide dans config('valyro.postback.token') → les boutons “rejouer” ne seront pas utiles.
                    </div>
                  @endif
                </details>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-6 py-10 text-center vy-muted">Aucune conversion.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-6 text-xs vy-muted border-t border-white/10">
      Tri conseillé : <span class="text-white font-semibold">received_at</span>. Payout stocké en points (1 pt = 0,01€).
      <div class="mt-3">
        {{ $conversions->withQueryString()->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
