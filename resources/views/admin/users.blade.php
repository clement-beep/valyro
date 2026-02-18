@extends('layouts.app')

@section('content')
@php
  $fmtPts = fn(int $pts) => number_format($pts, 0, ',', ' ') . ' pts';
  $fmtEur = fn(int $pts) => number_format($pts / 100, 2, ',', ' ') . ' €';
@endphp

<div class="space-y-6">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <div class="text-sm text-slate-400">Admin</div>
      <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">Utilisateurs</h1>
      <p class="text-slate-400 mt-1 text-sm">Liste des comptes + crédit de test.</p>
    </div>

    <div class="flex gap-2">
      <a href="{{ route('admin.withdrawals') }}"
         class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15">
        Admin retraits
      </a>

      <a href="{{ route('admin.conversions') }}"
         class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15">
        Conversions
      </a>
    </div>
  </div>

  <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
    <form method="GET" action="{{ route('admin.users') }}" class="flex flex-col sm:flex-row gap-2 sm:items-center">
      <input name="q" value="{{ $q }}" placeholder="Rechercher (id, nom, email)"
             class="w-full sm:w-96 rounded-xl border border-white/10 bg-slate-950/20 px-3 py-2 text-sm text-white" />
      <button class="rounded-xl bg-gradient-to-r from-indigo-500 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:opacity-95">
        Rechercher
      </button>
    </form>
  </div>

  <div class="rounded-3xl border border-white/10 bg-white/5 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-950/40 text-slate-300">
          <tr>
            <th class="text-left px-6 py-3 font-medium">User</th>
            <th class="text-left px-6 py-3 font-medium">Email</th>
            <th class="text-left px-6 py-3 font-medium">Inscription</th>
            <th class="text-left px-6 py-3 font-medium">Solde</th>
            <th class="text-left px-6 py-3 font-medium">En attente</th>
            <th class="text-right px-6 py-3 font-medium">Crédit test</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-white/5">
          @forelse($users as $u)
            @php
              $bal = (int) ($u->balance?->balance_cents ?? 0);
              $pen = (int) ($u->balance?->pending_cents ?? 0);
            @endphp
            <tr class="hover:bg-white/5">
              <td class="px-6 py-4 text-white font-semibold">#{{ $u->id }} — {{ $u->name }}</td>
              <td class="px-6 py-4 text-slate-300">{{ $u->email }}</td>
              <td class="px-6 py-4 text-slate-300 whitespace-nowrap">{{ optional($u->created_at)->format('d/m/Y H:i') }}</td>

              <td class="px-6 py-4 text-slate-200 whitespace-nowrap">
                {{ $fmtPts($bal) }} <span class="text-xs text-slate-400">(≈ {{ $fmtEur($bal) }})</span>
              </td>

              <td class="px-6 py-4 text-slate-200 whitespace-nowrap">
                {{ $fmtPts($pen) }} <span class="text-xs text-slate-400">(≈ {{ $fmtEur($pen) }})</span>
              </td>

              <td class="px-6 py-4">
                <form method="POST" action="{{ route('admin.users.credit', $u) }}" class="flex flex-col lg:flex-row gap-2 justify-end">
                  @csrf
                  <select name="mode" class="rounded-xl border border-white/10 bg-slate-950/20 px-3 py-2 text-sm text-white">
                    <option value="confirmed">Disponible</option>
                    <option value="pending">En attente</option>
                  </select>

                  <input type="number" name="amount_points" min="1" max="500000" value="1000"
                         class="w-36 rounded-xl border border-white/10 bg-slate-950/20 px-3 py-2 text-sm text-white"
                         placeholder="pts" />

                  <input name="note" class="w-52 rounded-xl border border-white/10 bg-slate-950/20 px-3 py-2 text-sm text-white"
                         placeholder="Note (optionnel)" />

                  <button onclick="return confirm('Créditer cet utilisateur ?')"
                          class="rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15">
                    Créditer
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-10 text-center text-slate-400">Aucun utilisateur.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-5 border-t border-white/10">
      {{ $users->links() }}
    </div>
  </div>
</div>
@endsection
