<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $layoutTitle ?? 'Valyro' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
      :root{
        --page:#0B0A18;
        --page-2:#141127;
        --surface: rgba(255,255,255,.06);
        --surface-2: rgba(255,255,255,.09);
        --border: rgba(255,255,255,.10);
        --text: rgba(255,255,255,.92);
        --muted: rgba(255,255,255,.70);
        --violet:#7C3AED;
        --magenta:#D946EF;
        --ruby:#E11D48;
        --gold:#F5C542;
        --shadow: 0 18px 55px rgba(0,0,0,.45);
      }
      html, body{
        height:100%;
        background: linear-gradient(180deg, var(--page), var(--page-2));
        color: var(--text);
      }
      .page-glow{
        background:
          radial-gradient(1100px 650px at 12% 8%, rgba(42,30,92,.50), transparent 60%),
          radial-gradient(980px 600px at 86% 10%, rgba(217,70,239,.24), transparent 62%),
          radial-gradient(1000px 620px at 76% 88%, rgba(225,29,72,.20), transparent 64%),
          radial-gradient(900px 520px at 50% 92%, rgba(245,197,66,.14), transparent 62%),
          linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,0));
      }
      .glass{
        background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.05));
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
      }
      .navlink{ border: 1px solid transparent; }
      .navlink:hover{
        background: rgba(255,255,255,.08);
        border-color: rgba(255,255,255,.08);
      }
      .navlink-active{
        background: rgba(255,255,255,.10);
        border-color: rgba(245,197,66,.18);
      }
      .btn-grad{
        background: linear-gradient(135deg, rgba(245,197,66,1), rgba(225,29,72,.92));
        color: rgba(12,10,22,.92);
        border: 1px solid rgba(255,255,255,.10);
      }
      .btn-grad:hover{ filter: brightness(1.03); }
      .muted{ color: var(--muted); }

      /* ✅ FIX GLOBAL FORM CONTROLS (inputs/select/textarea) */
      input:not([type="checkbox"]):not([type="radio"]),
      textarea,
      select{
        color: rgba(255,255,255,.92) !important;
        background-color: rgba(2,6,23,.22) !important;
        border-color: rgba(255,255,255,.10) !important;
        caret-color: rgba(255,255,255,.95);
      }
      input::placeholder,
      textarea::placeholder{
        color: rgba(148,163,184,.92) !important;
      }
      input:focus,
      textarea:focus,
      select:focus{
        outline: none !important;
        box-shadow: 0 0 0 2px rgba(99,102,241,.35) !important;
        border-color: rgba(255,255,255,.20) !important;
      }
      input::selection,
      textarea::selection{
        background: rgba(99,102,241,.35);
        color: rgba(226,232,240,1);
      }
      select option{
        background-color: rgb(15,23,42) !important;
        color: rgb(226,232,240) !important;
      }
      select:focus option:checked{
        background-color: rgb(30,27,75) !important;
        color: rgb(226,232,240) !important;
      }
    </style>
</head>

<body class="h-full">
<div class="min-h-full page-glow">

    {{-- Header --}}
    <header class="sticky top-0 z-50">
        <div class="glass border-b border-white/10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-4">

                    {{-- Brand --}}
                    <a href="{{ $navUrls['home'] }}" class="flex items-center gap-2">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-500 shadow-sm"></div>
                        <div class="leading-tight">
                            <div class="font-semibold text-white">Valyro</div>
                            <div class="text-xs muted">Points • Offres • Échanges</div>
                        </div>
                    </a>

                    {{-- Nav --}}
                    <nav class="hidden md:flex items-center gap-2">
                        @if($layoutIsLogged)

                            <a href="{{ $navUrls['dashboard'] }}"
                               class="navlink px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'navlink-active font-semibold' : 'text-white/80' }}">
                                Dashboard
                            </a>

                            <a href="{{ $navUrls['offers'] }}"
                               class="navlink px-3 py-2 rounded-lg text-sm {{ request()->routeIs('offers') ? 'navlink-active font-semibold' : 'text-white/80' }}">
                                Offres
                            </a>

                            <a href="{{ $navUrls['withdraw'] }}"
                               class="navlink px-3 py-2 rounded-lg text-sm {{ request()->routeIs('withdraw') ? 'navlink-active font-semibold' : 'text-white/80' }}">
                                Échanges
                            </a>

                            <a href="{{ $navUrls['profile'] }}"
                               class="navlink px-3 py-2 rounded-lg text-sm {{ request()->routeIs('profile.*') ? 'navlink-active font-semibold' : 'text-white/80' }}">
                                Profil
                            </a>

                            {{-- ✅ Admin NAV direct --}}
                            @if($layoutIsAdmin)
                                <a href="{{ route('admin.conversions') }}"
                                   class="navlink px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.conversions') ? 'navlink-active font-semibold' : 'text-white/80' }}">
                                    Admin • Postbacks
                                </a>

                                <a href="{{ route('admin.withdrawals') }}"
                                   class="navlink px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.withdrawals') ? 'navlink-active font-semibold' : 'text-white/80' }}">
                                    Admin • Retraits
                                </a>
                            @endif

                        @else
                            <a href="{{ $navUrls['login'] }}"
                               class="navlink px-3 py-2 rounded-lg text-sm text-white/80">
                                Connexion
                            </a>
                            <a href="{{ $navUrls['register'] }}"
                               class="px-3 py-2 rounded-lg text-sm font-semibold btn-grad shadow-sm">
                                Inscription
                            </a>
                        @endif
                    </nav>

                    {{-- Right --}}
                    @if($layoutIsLogged)
                        <div class="flex items-center gap-2">

                            <div class="hidden sm:block text-right">
                                <div class="text-sm font-medium text-white">{{ $layoutUserName }}</div>
                                <div class="text-xs muted">{{ $layoutUserEmail }}</div>
                            </div>

                            <div class="relative hidden md:block">
                                <div class="group relative">
                                    <div class="px-3 py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-sm font-semibold cursor-default select-none">
                                        Points <span class="text-white">{{ $layoutBalancePts }}</span>
                                        <span class="text-xs font-normal muted"> (≈ {{ $layoutBalanceEur }})</span>
                                    </div>

                                    <div class="pointer-events-none opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition absolute right-0 mt-2 w-72 z-50">
                                        <div class="glass rounded-2xl p-4">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-xs muted">Points disponibles</div>
                                                <span class="text-[11px] rounded-full bg-white/10 px-2.5 py-1 text-white/80">PTS</span>
                                            </div>

                                            <div class="mt-1 text-lg font-semibold text-white">
                                                {{ $layoutBalancePts }}
                                                <span class="text-sm font-normal muted"> (≈ {{ $layoutBalanceEur }})</span>
                                            </div>

                                            <div class="mt-3 rounded-xl border border-white/10 bg-white/5 p-3">
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-white/80">En attente</span>
                                                    <span class="font-semibold text-white">
                                                        {{ $layoutPendingPts }}
                                                        <span class="text-xs font-normal muted">(≈ {{ $layoutPendingEur }})</span>
                                                    </span>
                                                </div>
                                                <div class="mt-2 text-xs muted">
                                                    Les points “en attente” ne sont pas échangeables tant que l’offre n’est pas validée.
                                                </div>
                                            </div>

                                            <div class="mt-3 text-xs muted">
                                                Conversion : <span class="font-semibold text-white/80">1 pt = 0,01€</span>.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ $navUrls['logout'] }}">
                                @csrf
                                <button class="px-3 py-2 rounded-lg text-sm border border-white/10 bg-white/5 hover:bg-white/10">
                                    Déconnexion
                                </button>
                            </form>

                        </div>
                    @endif

                </div>
            </div>
        </div>
    </header>

    {{-- Main --}}
    <main class="bg-transparent">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
            <x-flash-messages />
            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 bg-white/5">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 text-sm muted flex flex-col md:flex-row gap-2 md:items-center md:justify-between">
            <div>© {{ date('Y') }} Valyro</div>
            <div class="flex gap-4">
                <a href="{{ $legalUrls['conditions'] }}" class="hover:text-white">Conditions</a>
                <a href="{{ $legalUrls['confidentialite'] }}" class="hover:text-white">Confidentialité</a>
                <a href="{{ $legalUrls['support'] }}" class="hover:text-white">Support</a>
            </div>
        </div>
    </footer>

</div>
<x-device-fingerprint />
</body>
</html>
