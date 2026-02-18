{{-- resources/views/profile/edit.blade.php --}}
@extends('layouts.app')

@section('content')
@php
  /** @var \App\Models\User $user */
  $user = request()->user();

  $adminEmail = (string) config('valyro.admin.email', 'maillet.clement.ifsi@gmail.com');
  $isAdmin = ($user && ($user->email ?? null) === $adminEmail);

  $confirmed = (int) optional($user->balance)->balance_cents;
  $pending   = (int) optional($user->balance)->pending_cents;

  $fmtPts = fn(int $pts) => number_format($pts, 0, ',', ' ') . ' pts';
  $fmtEur = fn(int $pts) => number_format($pts / 100, 2, ',', ' ') . ' €';

  $memberSince = $user?->created_at ? $user->created_at->format('d/m/Y') : '—';
  $emailVerified = !empty($user?->email_verified_at);
@endphp

<style>
/* =========================
   Valyro Profile Glass (pro)
   ========================= */
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
  background: radial-gradient(900px 240px at 12% 0%, rgba(124,58,237,.18), transparent 60%),
              radial-gradient(900px 240px at 88% 0%, rgba(225,29,72,.12), transparent 60%),
              radial-gradient(900px 240px at 60% 0%, rgba(245,197,66,.10), transparent 70%);
}
.vy-muted{ color: var(--vy-muted); }
.vy-label{ color: rgba(255,255,255,.76); font-size: 12px; }
.vy-value{ color: var(--vy-ink); font-weight: 650; }

.vy-field{
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
.vy-field:focus{
  outline: none;
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

.vy-btn{
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.06);
  padding: 10px 14px;
  font-weight: 750;
  color: rgba(255,255,255,.92);
  font-size: 14px;
  box-shadow: var(--vy-shadow2);
}
.vy-btn:hover{ background: rgba(255,255,255,.10); }

.vy-btn-primary{
  background: linear-gradient(135deg, rgba(245,197,66,1), rgba(225,29,72,.92));
  color: rgba(12,10,22,.92);
  border: 1px solid rgba(255,255,255,.10);
}
.vy-btn-primary:hover{ filter: brightness(1.03); }

.vy-badge{
  display:inline-flex; align-items:center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.08);
  color: rgba(255,255,255,.86);
}
.vy-badge-ok{ border-color: rgba(16,185,129,.28); background: rgba(16,185,129,.14); color: rgba(167,243,208,.98); }
.vy-badge-warn{ border-color: rgba(245,197,66,.24); background: rgba(245,197,66,.12); color: rgba(253,230,138,.98); }
.vy-badge-admin{ border-color: rgba(217,70,239,.24); background: rgba(217,70,239,.12); color: rgba(240,171,252,.98); }

.vy-sep{ border-top: 1px solid rgba(255,255,255,.10); }
</style>

<div class="space-y-6">

  {{-- Header --}}
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <div class="text-sm vy-muted">Compte</div>
      <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">Profil</h1>
      <p class="text-sm vy-muted mt-1">
        Gère ton compte, tes infos essentielles et ta sécurité.
      </p>
    </div>

    <div class="flex items-center gap-2">
      @if($isAdmin)
        <span class="vy-badge vy-badge-admin">Admin</span>
      @else
        <span class="vy-badge">Utilisateur</span>
      @endif

      @if($emailVerified)
        <span class="vy-badge vy-badge-ok">Email vérifié</span>
      @else
        <span class="vy-badge vy-badge-warn">Email non vérifié</span>
      @endif
    </div>
  </div>

  {{-- Overview (cadre premium) --}}
  <div class="vy-card overflow-hidden">
    <div class="p-6 vy-card-h">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        <div class="flex items-center gap-4">
          @php $initials = strtoupper(mb_substr((string)($user->name ?? 'U'),0,1)); @endphp

          <div class="h-14 w-14 rounded-2xl flex items-center justify-center font-extrabold text-lg"
               style="background: linear-gradient(135deg, rgba(124,58,237,.95), rgba(217,70,239,.55)); border: 1px solid rgba(255,255,255,.14); box-shadow: 0 12px 30px rgba(0,0,0,.25);">
            {{ $initials }}
          </div>

          <div>
            <div class="text-lg font-semibold text-white">{{ $user->name ?? '—' }}</div>
            <div class="text-sm vy-muted">{{ $user->email ?? '—' }}</div>
            <div class="mt-2 flex flex-wrap gap-2">
              <span class="vy-badge">ID #{{ $user->id ?? '—' }}</span>
              <span class="vy-badge">Membre depuis {{ $memberSince }}</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 w-full md:w-auto">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4"
               style="box-shadow: 0 12px 30px rgba(0,0,0,.22);">
            <div class="vy-label">Points confirmés</div>
            <div class="mt-1 text-lg font-semibold text-white">{{ $fmtPts($confirmed) }}</div>
            <div class="text-xs vy-muted">≈ {{ $fmtEur($confirmed) }}</div>
          </div>

          <div class="rounded-2xl border border-white/10 bg-white/5 p-4"
               style="box-shadow: 0 12px 30px rgba(0,0,0,.22);">
            <div class="vy-label">Points en attente</div>
            <div class="mt-1 text-lg font-semibold text-white">{{ $fmtPts($pending) }}</div>
            <div class="text-xs vy-muted">≈ {{ $fmtEur($pending) }}</div>
          </div>
        </div>

      </div>
    </div>

    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4"
             style="box-shadow: 0 12px 30px rgba(0,0,0,.18);">
          <div class="vy-label">Statut du compte</div>
          <div class="mt-1 vy-value">{{ $emailVerified ? 'Actif (email vérifié)' : 'Actif (email non vérifié)' }}</div>
          <div class="text-xs vy-muted mt-1">Sécurise ton compte avec un mot de passe fort.</div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-4"
             style="box-shadow: 0 12px 30px rgba(0,0,0,.18);">
          <div class="vy-label">Rôle</div>
          <div class="mt-1 vy-value">{{ $isAdmin ? 'Administrateur' : 'Utilisateur' }}</div>
          <div class="text-xs vy-muted mt-1">Accès à l’admin si email autorisé.</div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-4"
             style="box-shadow: 0 12px 30px rgba(0,0,0,.18);">
          <div class="vy-label">Conversion</div>
          <div class="mt-1 vy-value">1 pt = 0,01€</div>
          <div class="text-xs vy-muted mt-1">Retraits PayPal via la page Échanges.</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Settings grid --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Update profile --}}
    <div class="vy-card overflow-hidden">
      <div class="p-6 vy-card-h">
        <h2 class="text-lg font-semibold text-white">Informations du profil</h2>
        <p class="text-sm vy-muted mt-1">Modifie ton nom et ton email.</p>
      </div>

      <div class="p-6">
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
          @csrf
          @method('PATCH')

          <div>
            <label class="vy-label">Nom</label>
            <input class="vy-field mt-1" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>
            @error('name') <div class="text-xs text-rose-300 mt-1">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="vy-label">Email</label>
            <input class="vy-field mt-1" type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
            @error('email') <div class="text-xs text-rose-300 mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button class="vy-btn vy-btn-primary" type="submit">Enregistrer</button>
            <span class="text-xs vy-muted">Les changements s’appliquent immédiatement.</span>
          </div>

          @if (session('status') === 'profile-updated')
            <div class="text-sm text-emerald-200 mt-2">Profil mis à jour ✅</div>
          @endif
        </form>
      </div>
    </div>

    {{-- Update password --}}
    <div class="vy-card overflow-hidden">
      <div class="p-6 vy-card-h">
        <h2 class="text-lg font-semibold text-white">Sécurité</h2>
        <p class="text-sm vy-muted mt-1">Change ton mot de passe régulièrement.</p>
      </div>

      <div class="p-6">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
          @csrf
          @method('PUT')

          <div>
            <label class="vy-label">Mot de passe actuel</label>
            <input class="vy-field mt-1" type="password" name="current_password" autocomplete="current-password" required>
            @error('current_password') <div class="text-xs text-rose-300 mt-1">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="vy-label">Nouveau mot de passe</label>
            <input class="vy-field mt-1" type="password" name="password" autocomplete="new-password" required>
            @error('password') <div class="text-xs text-rose-300 mt-1">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="vy-label">Confirmer le nouveau mot de passe</label>
            <input class="vy-field mt-1" type="password" name="password_confirmation" autocomplete="new-password" required>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button class="vy-btn vy-btn-primary" type="submit">Mettre à jour</button>
            <span class="text-xs vy-muted">Utilise un mot de passe long et unique.</span>
          </div>

          @if (session('status') === 'password-updated')
            <div class="text-sm text-emerald-200 mt-2">Mot de passe mis à jour ✅</div>
          @endif
        </form>
      </div>
    </div>

  </div>

  {{-- Danger zone --}}
  <div class="vy-card overflow-hidden">
    <div class="p-6 vy-card-h">
      <h2 class="text-lg font-semibold text-white">Zone dangereuse</h2>
      <p class="text-sm vy-muted mt-1">Suppression définitive du compte.</p>
    </div>

    <div class="p-6">
      <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4" style="box-shadow: 0 12px 30px rgba(0,0,0,.20);">
        <div class="text-white font-semibold">Supprimer mon compte</div>
        <div class="text-sm vy-muted mt-1">
          Cette action est irréversible (données + historique).
        </div>

        <div class="vy-sep my-4"></div>

        <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Confirmer la suppression définitive du compte ?');">
          @csrf
          @method('DELETE')

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
            <div>
              <label class="vy-label">Mot de passe</label>
              <input class="vy-field mt-1" type="password" name="password" autocomplete="current-password" required>
              @error('password') <div class="text-xs text-rose-200 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="flex md:justify-end">
              <button class="vy-btn" type="submit" style="border-color: rgba(244,63,94,.35); background: rgba(244,63,94,.14); color: rgba(254,202,202,.98);">
                Supprimer définitivement
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>

</div>
@endsection
