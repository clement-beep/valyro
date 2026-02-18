{{-- resources/views/welcome.blade.php --}}
@extends('layouts.app')

@section('content')
<style>
  :root{
    --page: #0B0A18;
    --page-2: #141127;

    --surface: rgba(255,255,255,.06);
    --surface-2: rgba(255,255,255,.09);

    --border: rgba(255,255,255,.10);
    --border-2: rgba(255,255,255,.16);

    --text: rgba(255,255,255,.92);
    --muted: rgba(255,255,255,.70);
    --muted-2: rgba(255,255,255,.58);

    --violet: #7C3AED;
    --ruby: #E11D48;
    --gold: #F5C542;
    --magenta: #D946EF;
    --indigo: #6366F1;
    --success: #10B981;

    --radius-xl: 1.25rem;

    --shadow-soft: 0 12px 30px rgba(0,0,0,.35);
    --shadow-card: 0 22px 70px rgba(0,0,0,.55);

    --ring-strong: 0 0 0 1px rgba(245,197,66,.30), 0 0 0 6px rgba(245,197,66,.10);
  }

  html, body{
    background: linear-gradient(180deg, var(--page), var(--page-2)) !important;
    color: var(--text);
  }
  body > #app,
  #app,
  main,
  .min-h-screen,
  .bg-white,
  .bg-gray-50,
  .bg-slate-50{
    background: transparent !important;
  }

  /* Neutraliser le wrapper Breeze sur welcome uniquement */
  main .bg-gray-800,
  main .bg-gray-900,
  main .bg-slate-800,
  main .bg-slate-900{
    background: transparent !important;
  }
  main .shadow,
  main .shadow-sm,
  main .shadow-md,
  main .shadow-lg{ box-shadow: none !important; }
  main .rounded,
  main .rounded-lg,
  main .rounded-xl,
  main .rounded-2xl,
  main .rounded-3xl{ border-radius: 0 !important; }
  main > .py-12 > .max-w-7xl > div > .p-6{ padding: 0 !important; }

  header a .text-xs,
  header a .text-sm.text-slate-500,
  header a .text-slate-500.text-sm,
  header a .text-slate-500.text-xs{ display:none !important; }

  header{
    background: linear-gradient(90deg, rgba(12,10,24,.78), rgba(42,30,92,.52)) !important;
    border-color: rgba(255,255,255,.10) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
  }
  header a, header button, header nav a{ color: rgba(255,255,255,.92) !important; }
  header nav a:hover{ background: rgba(255,255,255,.08) !important; }
  header .text-slate-400, header .text-slate-500{ color: rgba(255,255,255,.70) !important; }

  .text-title{ color: var(--text) !important; }
  .text-body{ color: var(--muted) !important; }
  .text-muted2{ color: var(--muted-2) !important; }

  /* ✅ Fond premium lissé + nuance or */
  .welcome-wrap{
    position: relative;
    isolation: isolate;
    padding: 40px 0;
  }
  .welcome-wrap::before{
    content:"";
    position:absolute;
    inset:-220px -140px;
    z-index:-2;
    pointer-events:none;
    background:
      radial-gradient(1200px 660px at 50% 34%, rgba(255,255,255,.075), transparent 70%),
      radial-gradient(980px 560px at 18% 16%, rgba(124,58,237,.22), transparent 64%),
      radial-gradient(980px 560px at 86% 14%, rgba(217,70,239,.18), transparent 64%),
      radial-gradient(980px 560px at 78% 86%, rgba(225,29,72,.16), transparent 66%),
      radial-gradient(1150px 640px at 55% 72%, rgba(245,197,66,.20), transparent 68%),
      radial-gradient(900px 520px at 30% 80%, rgba(245,197,66,.12), transparent 70%);
  }
  .welcome-wrap::after{
    content:"";
    position:absolute;
    inset:-160px -100px;
    z-index:-1;
    pointer-events:none;
    background-image: radial-gradient(rgba(255,255,255,.05) 1px, transparent 1px);
    background-size: 18px 18px;
    opacity:.10;
    mask-image: radial-gradient(760px 560px at 50% 34%, #000 45%, transparent 78%);
  }

  .sep{
    height: 1px;
    width: 100%;
    background: linear-gradient(
      90deg,
      transparent,
      rgba(124,58,237,.28),
      rgba(217,70,239,.18),
      rgba(225,29,72,.16),
      rgba(245,197,66,.32),
      transparent
    );
  }

  /* Chips */
  .chip{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    border-radius:999px;
    padding:.35rem .75rem;
    border:1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.82);
    font-size:.9rem;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
  }
  .dot{ width:.5rem; height:.5rem; border-radius:999px; display:inline-block; }

  /* Button */
  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.5rem;
    border-radius:999px;
    padding:.75rem 1.05rem;
    font-weight:800;
    line-height:1;
    transition: transform .15s ease, box-shadow .2s ease, filter .2s ease;
    user-select:none;
    -webkit-tap-highlight-color: transparent;
  }
  .btn:focus{ outline:none; box-shadow: var(--ring-strong); }
  .btn-primary{
    color: rgba(12,10,22,.92);
    background: linear-gradient(135deg, rgba(245,197,66,1), rgba(225,29,72,.92));
    border: 1px solid rgba(255,255,255,.10);
    box-shadow: 0 18px 44px rgba(225,29,72,.20), 0 14px 38px rgba(245,197,66,.18);
  }
  .btn-primary:hover{
    filter: brightness(1.05);
    transform: translateY(-1px);
    box-shadow: 0 22px 56px rgba(225,29,72,.24), 0 18px 50px rgba(245,197,66,.22);
  }

  /* ✅ Cards premium */
  .premium-card{
    position: relative;
    border-radius: var(--radius-xl);
    background: linear-gradient(180deg, rgba(255,255,255,.075), rgba(255,255,255,.035));
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: var(--shadow-soft);
    overflow: hidden;
    transition: transform .18s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
  }
  .premium-card::before{
    content:"";
    position:absolute;
    inset:-1px;
    border-radius: var(--radius-xl);
    padding: 1px;
    background: linear-gradient(90deg,
      rgba(245,197,66,.90),
      rgba(217,70,239,.32),
      rgba(124,58,237,.32),
      rgba(225,29,72,.28),
      rgba(245,197,66,.90)
    );
    -webkit-mask:
      linear-gradient(#000 0 0) content-box,
      linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    opacity: .58;
    pointer-events:none;
  }
  .premium-card::after{
    content:"";
    position:absolute;
    inset:0;
    border-radius: var(--radius-xl);
    background:
      radial-gradient(520px 260px at 20% 20%, rgba(255,255,255,.08), transparent 60%),
      radial-gradient(520px 260px at 80% 10%, rgba(245,197,66,.08), transparent 62%);
    opacity:.70;
    pointer-events:none;
  }
  .premium-card:hover{
    transform: translateY(-2px);
    box-shadow: var(--shadow-card);
    border-color: rgba(245,197,66,.20);
    background: linear-gradient(180deg, rgba(255,255,255,.085), rgba(255,255,255,.04));
  }
  .premium-card > *{ position: relative; z-index: 1; }

  /* ✅ Badge icône premium (SVG) */
  .icon-badge{
    width: 42px;
    height: 42px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.14);
    background:
      radial-gradient(18px 18px at 30% 30%, rgba(255,255,255,.18), transparent 60%),
      linear-gradient(180deg, rgba(255,255,255,.09), rgba(255,255,255,.04));
    box-shadow: 0 16px 40px rgba(0,0,0,.35);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    position: relative;
    overflow:hidden;
    flex: 0 0 auto;
  }
  .icon-badge::before{
    content:"";
    position:absolute;
    inset:-1px;
    border-radius: 14px;
    opacity:.55;
    pointer-events:none;
    background: linear-gradient(135deg, rgba(124,58,237,.55), rgba(217,70,239,.25), rgba(245,197,66,.28));
  }
  .icon-badge svg{
    position: relative;
    width: 20px;
    height: 20px;
    stroke: rgba(255,255,255,.92);
    stroke-width: 1.8;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,.25));
  }
  .ib-indigo::before{ background: linear-gradient(135deg, rgba(99,102,241,.55), rgba(124,58,237,.35), rgba(245,197,66,.22)); }
  .ib-ruby::before{ background: linear-gradient(135deg, rgba(225,29,72,.45), rgba(217,70,239,.32), rgba(245,197,66,.22)); }
  .ib-emerald::before{ background: linear-gradient(135deg, rgba(16,185,129,.35), rgba(124,58,237,.35), rgba(245,197,66,.22)); }

  /* Hero card */
  .card-hero{
    position: relative;
    border-radius: var(--radius-xl);
    background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.05));
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 0 0 1px rgba(255,255,255,.06), 0 28px 70px rgba(0,0,0,.48);
    overflow:hidden;
  }
  .card-hero::before{
    content:"";
    position:absolute;
    inset:-1px;
    border-radius: var(--radius-xl);
    background: linear-gradient(90deg,
      rgba(124,58,237,.28),
      rgba(217,70,239,.18),
      rgba(225,29,72,.16),
      rgba(245,197,66,.22)
    );
    opacity:.55;
    z-index:0;
  }
  .card-hero > *{ position:relative; z-index:1; }

  .hero-media{
    border-radius: var(--radius-xl);
    overflow:hidden;
    border:1px solid rgba(255,255,255,.12);
    background:
      radial-gradient(900px 460px at 40% 30%, rgba(124,58,237,.22), transparent 64%),
      radial-gradient(900px 460px at 70% 65%, rgba(245,197,66,.16), transparent 66%),
      linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
    aspect-ratio: 16/9;
  }
  .hero-media img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    filter: saturate(1.06) contrast(1.04);
  }

  /* FAQ details */
  details{ transition: border-color .2s ease, box-shadow .2s ease, background .2s ease; }
  details:hover{
    border-color: rgba(245,197,66,.24) !important;
    background: rgba(255,255,255,.07) !important;
    box-shadow: 0 18px 50px rgba(0,0,0,.28);
  }
  summary{ list-style:none; }
  summary::-webkit-details-marker{ display:none; }

  ::selection{ background: rgba(245,197,66,.28); color: rgba(255,255,255,.95); }
</style>

<div class="welcome-wrap px-4 sm:px-6 lg:px-8">
  <div class="mx-auto max-w-7xl">

    {{-- HERO --}}
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs shadow-sm"
             style="background: var(--surface); border-color: var(--border); color: var(--muted);">
          <span class="inline-flex h-2 w-2 rounded-full" style="background:var(--success)"></span>
          Points Valyro • Sondages • Offres partenaires
        </div>

        <h1 class="mt-4 text-4xl sm:text-5xl font-extrabold leading-tight text-title">
          Gagne des Points Valyro en complétant des sondages & offres simples
        </h1>

        <p class="mt-4 text-lg text-body max-w-xl">
          Des missions rapides, des <span class="font-semibold text-title">Points Valyro annoncés</span>, et un parcours clair.
          Tu sais ce qu’il faut faire <span class="font-semibold text-title">avant</span> de cliquer.
        </p>

        <div class="mt-6 flex flex-wrap gap-2">
          <span class="chip"><span class="dot" style="background:var(--indigo)"></span>Rapide</span>
          <span class="chip"><span class="dot" style="background:var(--magenta)"></span>Lisible</span>
          <span class="chip"><span class="dot" style="background:var(--success)"></span>Suivi propre</span>
          <span class="chip"><span class="dot" style="background:var(--gold)"></span>Points échangeables</span>
        </div>

        <div class="mt-8">
          @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn btn-primary">
              <span aria-hidden="true">✨</span> Commencer
            </a>
          @else
            <a href="/offers" class="btn btn-primary">
              <span aria-hidden="true">✨</span> Voir les offres
            </a>
          @endif
        </div>

        <p class="mt-4 text-sm text-muted2 max-w-xl">
          Les Points Valyro sont une monnaie virtuelle interne. Tu peux ensuite les <span class="font-semibold text-title">échanger</span>
          contre un paiement PayPal à partir d’un seuil, après validation des offres.
        </p>
      </div>

      {{-- VISUEL HERO --}}
      <div>
        <div class="card-hero p-4 sm:p-5">
          <div class="hero-media">
            <img
              src="{{ asset('images/hero-720x520.png') }}"
              alt="Aperçu Valyro"
              width="720"
              height="520"
            >
          </div>
        </div>
      </div>
    </section>

    <div class="my-12 sep"></div>

    {{-- 3 CARTES --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="premium-card p-6">
        <div class="flex items-center gap-3">
          <span class="icon-badge ib-indigo" aria-hidden="true">
            {{-- Target --}}
            <svg viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="7"></circle>
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 2v3"></path>
              <path d="M22 12h-3"></path>
              <path d="M12 22v-3"></path>
              <path d="M2 12h3"></path>
            </svg>
          </span>
          <h3 class="text-base font-semibold text-title">Objectif clair</h3>
        </div>
        <p class="mt-3 text-body">
          Avant de commencer, tu vois ce qui est attendu et combien de <span class="font-semibold text-title">Points Valyro</span> tu gagnes. Zéro flou.
        </p>
      </div>

      <div class="premium-card p-6">
        <div class="flex items-center gap-3">
          <span class="icon-badge ib-ruby" aria-hidden="true">
            {{-- Zap --}}
            <svg viewBox="0 0 24 24">
              <path d="M13 2L3 14h8l-1 8 11-14h-8l0-6z"></path>
            </svg>
          </span>
          <h3 class="text-base font-semibold text-title">Missions rapides</h3>
        </div>
        <p class="mt-3 text-body">
          Sondages, inscriptions, tests… tu avances vite, avec des consignes simples et compréhensibles.
        </p>
      </div>

      <div class="premium-card p-6">
        <div class="flex items-center gap-3">
          <span class="icon-badge ib-emerald" aria-hidden="true">
            {{-- Clipboard list --}}
            <svg viewBox="0 0 24 24">
              <path d="M9 4h6"></path>
              <path d="M9 3h6a2 2 0 0 1 2 2v1H7V5a2 2 0 0 1 2-2z"></path>
              <path d="M8 6H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-2"></path>
              <path d="M8 12h8"></path>
              <path d="M8 16h6"></path>
            </svg>
          </span>
          <h3 class="text-base font-semibold text-title">Suivi lisible</h3>
        </div>
        <p class="mt-3 text-body">
          Statuts simples + historique propre : tu sais où tu en es, sans te perdre.
        </p>
      </div>
    </section>

    <div class="my-12 sep"></div>

    {{-- COMMENT ÇA MARCHE ? --}}
    <section class="premium-card p-8">
      <div class="flex items-start justify-between gap-6 flex-col md:flex-row">
        <div class="max-w-2xl">
          <h2 class="text-2xl font-extrabold text-title">Comment ça marche ?</h2>
          <p class="mt-2 text-body">
            Tu choisis une offre, tu fais l’action demandée, tu suis la validation, puis tu récupères tes <span class="font-semibold text-title">Points Valyro</span>.
          </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <span class="rounded-full px-3 py-1 text-sm border" style="border-color:var(--border); background: rgba(99,102,241,.14); color: rgba(224,231,255,.95);">1. Choisir</span>
          <span style="color:rgba(245,197,66,.95)">→</span>
          <span class="rounded-full px-3 py-1 text-sm border" style="border-color:var(--border); background: rgba(217,70,239,.14); color: rgba(250,232,255,.95);">2. Valider</span>
          <span style="color:rgba(245,197,66,.95)">→</span>
          <span class="rounded-full px-3 py-1 text-sm border" style="border-color:var(--border); background: rgba(245,197,66,.14); color: rgba(255,249,235,.95);">3. Points</span>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border p-5" style="border-color:var(--border); background: var(--surface);">
          <div class="text-sm font-semibold text-title">Choix guidé</div>
          <div class="mt-1 text-body text-sm">Tu repères rapidement les parcours les plus simples.</div>
        </div>

        <div class="rounded-2xl border p-5" style="border-color:var(--border); background: var(--surface);">
          <div class="text-sm font-semibold text-title">Validation propre</div>
          <div class="mt-1 text-body text-sm">Objectifs clairs, étapes maîtrisées, moins de confusion.</div>
        </div>

        <div class="rounded-2xl border p-5" style="border-color:var(--border); background: var(--surface);">
          <div class="text-sm font-semibold text-title">Points annoncés</div>
          <div class="mt-1 text-body text-sm">Tu sais combien de Points Valyro tu gagnes avant de commencer.</div>
        </div>
      </div>
    </section>

    <div class="my-12 sep"></div>

    {{-- POURQUOI VALYRO --}}
    <section>
      <div class="max-w-3xl">
        <h2 class="text-3xl font-extrabold text-title">Pourquoi Valyro ?</h2>
        <p class="mt-2 text-body">
          Une plateforme pensée pour des validations propres, des parcours clairs et une expérience premium.
        </p>
      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="premium-card p-6">
          <div class="flex items-center gap-3">
            <span class="icon-badge ib-indigo" aria-hidden="true">
              {{-- Sparkles --}}
              <svg viewBox="0 0 24 24">
                <path d="M12 2l1.2 3.6L17 7l-3.8 1.4L12 12l-1.2-3.6L7 7l3.8-1.4L12 2z"></path>
                <path d="M19 12l.8 2.4L22 15l-2.2.6L19 18l-.8-2.4L16 15l2.2-.6L19 12z"></path>
                <path d="M5 12l.8 2.4L8 15l-2.2.6L5 18l-.8-2.4L2 15l2.2-.6L5 12z"></path>
              </svg>
            </span>
            <h3 class="text-base font-semibold text-title">Offres sélectionnées & rentables</h3>
          </div>
          <p class="mt-3 text-body">
            <span class="font-semibold text-title">Pas d’offres au hasard.</span><br>
            On met en avant des parcours adaptés, compréhensibles et réellement récompensants, pour maximiser tes chances de valider rapidement.
          </p>
        </div>

        <div class="premium-card p-6">
          <div class="flex items-center gap-3">
            <span class="icon-badge ib-emerald" aria-hidden="true">
              {{-- Shield --}}
              <svg viewBox="0 0 24 24">
                <path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"></path>
                <path d="M9 12l2 2 4-5"></path>
              </svg>
            </span>
            <h3 class="text-base font-semibold text-title">Sécurité & fiabilité</h3>
          </div>
          <p class="mt-3 text-body">
            Un environnement sécurisé, pensé pour éviter les abus et protéger tes gains.<br>
            Chaque validation est vérifiée pour garantir un système juste, sans triche ni mauvaises surprises.
          </p>
        </div>

        <div class="premium-card p-6">
          <div class="flex items-center gap-3">
            <span class="icon-badge ib-ruby" aria-hidden="true">
              {{-- Eye --}}
              <svg viewBox="0 0 24 24">
                <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </span>
            <h3 class="text-base font-semibold text-title">Transparence totale</h3>
          </div>
          <p class="mt-3 text-body">
            Tu sais exactement ce que tu dois faire avant de commencer.<br>
            Pas de pièges, pas de chaînes infinies : un objectif clair, un parcours propre, des Points Valyro annoncés.
          </p>
        </div>
      </div>
    </section>

    <div class="my-12 sep"></div>

    {{-- FAQ --}}
    <section id="faq" class="premium-card p-8">
      <div>
        <h2 class="text-2xl font-extrabold text-title">FAQ</h2>
        <p class="mt-1 text-body">Réponses rapides, sans jargon.</p>
      </div>

      <div class="mt-6 space-y-3">
        <details class="rounded-2xl border p-5" style="border-color:var(--border); background: var(--surface);">
          <summary class="cursor-pointer font-semibold text-title">Les Points Valyro, c’est quoi ?</summary>
          <p class="mt-2 text-body">
            Les Points Valyro sont une monnaie virtuelle interne. Tu en gagnes quand une offre est validée.
            Ensuite, tu peux les <span class="font-semibold text-title">échanger</span> contre un paiement PayPal à partir d’un seuil.
          </p>
        </details>

        <details class="rounded-2xl border p-5" style="border-color:var(--border); background: var(--surface);">
          <summary class="cursor-pointer font-semibold text-title">C’est quoi un “sondage” ici ?</summary>
          <p class="mt-2 text-body">
            Une mission courte où tu réponds à quelques questions. L’objectif et les Points Valyro sont annoncés avant de commencer.
          </p>
        </details>

        <details class="rounded-2xl border p-5" style="border-color:var(--border); background: var(--surface);">
          <summary class="cursor-pointer font-semibold text-title">Pourquoi certains points sont “En attente” ?</summary>
          <p class="mt-2 text-body">
            Certaines validations prennent un délai partenaire (anti-fraude / vérification). Une fois validé, tes Points Valyro deviennent disponibles.
          </p>
        </details>

        <details class="rounded-2xl border p-5" style="border-color:var(--border); background: var(--surface);">
          <summary class="cursor-pointer font-semibold text-title">Comment fonctionne l’échange PayPal ?</summary>
          <p class="mt-2 text-body">
            Quand tes Points Valyro sont disponibles, tu peux faire une demande d’échange PayPal à partir d’un seuil.
            Tu retrouves aussi l’historique et le statut de chaque demande.
          </p>
        </details>
      </div>
    </section>

  </div>
</div>
@endsection
