@extends('layouts.app')

@section('content')
<style>
  :root{
    --page:#0B0A18; --page-2:#141127;
    --surface:rgba(255,255,255,.06);
    --border:rgba(255,255,255,.10);
    --text:rgba(255,255,255,.92); --muted:rgba(255,255,255,.70);
    --gold:#F5C542; --violet:#7C3AED; --ruby:#E11D48;
    --radius-xl:1.25rem;
    --shadow: 0 18px 55px rgba(0,0,0,.45);
    --ring: 0 0 0 1px rgba(245,197,66,.28), 0 0 0 6px rgba(245,197,66,.10);
  }
  html, body{ background: linear-gradient(180deg,var(--page),var(--page-2)) !important; color: var(--text); }
  body > #app, #app, main, .min-h-screen, .bg-white, .bg-gray-50{ background: transparent !important; }

  .page-wrap{ padding: 2.5rem 1rem; }
  .container{ max-width: 980px; margin: 0 auto; }

  .topbar{
    display:flex; align-items:center; justify-content:space-between; gap: .75rem;
    margin-bottom: 1rem;
  }
  .crumbs{
    display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
    color: rgba(255,255,255,.70); font-size: .92rem;
  }
  .crumbs a{ color: rgba(245,197,66,.95); text-decoration: underline; text-underline-offset: 3px; }
  .crumbs a:hover{ filter: brightness(1.05); }

  .btn-back{
    display:inline-flex; align-items:center; gap:.5rem;
    border-radius: 999px;
    padding: .6rem .9rem;
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.90);
    font-weight: 700;
    transition: transform .15s ease, background .2s ease, border-color .2s ease;
    user-select:none; -webkit-tap-highlight-color: transparent;
  }
  .btn-back:hover{
    transform: translateY(-1px);
    background: rgba(255,255,255,.08);
    border-color: rgba(245,197,66,.22);
  }
  .btn-back:focus{ outline:none; box-shadow: var(--ring); }

  .card{
    border: 1px solid var(--border);
    background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.05));
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow);
    overflow: hidden;
  }
  .card-inner{ padding: 1.5rem; }
  @media (min-width: 640px){ .card-inner{ padding: 2rem; } }

  .kicker{
    display:inline-flex; align-items:center; gap:.5rem;
    border:1px solid var(--border);
    background: rgba(255,255,255,.06);
    padding:.35rem .75rem; border-radius:999px;
    color: rgba(255,255,255,.72);
    font-size: .85rem;
  }
  .dot{ width:.5rem; height:.5rem; border-radius:999px; display:inline-block; background: var(--gold); }

  h1{ font-size: 1.75rem; line-height: 1.2; font-weight: 900; }
  @media (min-width: 640px){ h1{ font-size: 2.15rem; } }

  h2{ margin-top: 1.75rem; font-size: 1.15rem; font-weight: 900; }
  h3{ margin-top: 1.25rem; font-size: 1.02rem; font-weight: 900; }

  p, li{ color: var(--muted); }
  p{ margin-top: .65rem; }
  ul{ margin-top: .65rem; padding-left: 1.1rem; list-style: disc; }
  li{ margin-top: .35rem; }

  .sep{
    height: 1px; width: 100%;
    background: linear-gradient(90deg, transparent, rgba(124,58,237,.25), rgba(225,29,72,.14), rgba(245,197,66,.18), transparent);
    margin: 1.25rem 0 0;
  }

  .notice{
    margin-top: 1rem;
    border: 1px solid rgba(245,197,66,.22);
    background: rgba(245,197,66,.08);
    border-radius: 1rem;
    padding: .9rem 1rem;
    color: rgba(255,249,235,.92);
  }

  .danger{
    margin-top: 1rem;
    border: 1px solid rgba(244,63,94,.28);
    background: rgba(244,63,94,.10);
    border-radius: 1rem;
    padding: .9rem 1rem;
    color: rgba(255,255,255,.90);
  }

  a.link{
    color: rgba(245,197,66,.95);
    text-decoration: underline;
    text-underline-offset: 3px;
  }
  a.link:hover{ filter: brightness(1.05); }

  .small{ font-size: .92rem; color: rgba(255,255,255,.65); }
  .badge{
    display:inline-flex; align-items:center; gap:.5rem;
    padding:.25rem .6rem; border-radius:999px;
    border:1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.82);
    font-size: .85rem;
  }
</style>

@php
  $supportEmail = config('app.support_email', 'contact@valyro.fr');
@endphp

<div class="page-wrap">
  <div class="container">

    <div class="topbar">
      <div class="crumbs">
        <a href="{{ route('home') }}">Accueil</a>
        <span>›</span>
        <span>Conditions d’utilisation</span>
      </div>

      <a class="btn-back" href="{{ route('home') }}">
        ← Retour à l’accueil
      </a>
    </div>

    <div class="card">
      <div class="card-inner">
        <div class="kicker"><span class="dot"></span> Pages légales</div>

        <h1 class="mt-3">Conditions d’utilisation</h1>
        <p class="small">
          Mise à jour : {{ now()->format('d/m/Y') }}.
        </p>

        <div class="sep"></div>

        <p>
          Bienvenue sur Valyro. Ces conditions expliquent simplement comment fonctionne le site,
          ce qui est autorisé, et ce qu’on fait en cas d’abus.
          En créant un compte ou en utilisant Valyro, tu acceptes ces règles.
        </p>

        <h2>1) À quoi sert Valyro ?</h2>
        <p>
          Valyro te permet de gagner des points en réalisant des actions proposées par des partenaires
          (ex : sondages, inscriptions, tests, applications).
          Ensuite, tu peux échanger tes points quand ils sont disponibles.
        </p>

        <p class="notice">
          Important : une action faite ne veut pas dire “gain garanti”.
          La validation dépend du partenaire (contrôles, délais, règles à respecter).
        </p>

        <h2>2) Ton compte</h2>
        <ul>
          <li>Tu utilises des infos réelles (au minimum un email valide).</li>
          <li><strong>Un seul compte par personne</strong>. Plusieurs comptes = blocage.</li>
          <li>Tu gardes ton accès pour toi (pas de partage / revente).</li>
          <li><strong>VPN / proxy interdits</strong> : l’utilisation d’un VPN, proxy, “IP changer”, ou d’un outil qui masque ta connexion n’est pas autorisée.</li>
        </ul>

        <div class="danger">
          <strong>VPN / proxy :</strong> si tu utilises ce type d’outil, une offre peut être refusée,
          et ton compte peut être mis en pause le temps de vérification (ou fermé en cas d’abus).
        </div>

        <h2>3) Comment obtenir des points</h2>
        <h3>Avant de commencer une offre</h3>
        <ul>
          <li>Lis bien ce qui est demandé (pays, âge, appareil, délai, etc.).</li>
          <li>Fais l’offre en une seule session si possible (évite de changer d’appareil en cours).</li>
          <li><strong>Ne te connecte pas via VPN/proxy</strong> : fais les offres avec une connexion normale.</li>
          <li>Évite les bloqueurs trop agressifs : ça peut empêcher le suivi et la validation.</li>
        </ul>

        <h3>Les statuts de tes gains</h3>
        <ul>
          <li><strong>En attente</strong> : c’est pris en compte, mais pas encore validé.</li>
          <li><strong>Validé</strong> : les points sont ajoutés à ton solde.</li>
          <li><strong>Refusé</strong> : l’action n’a pas été acceptée par le partenaire.</li>
        </ul>

        <h3>Les raisons les plus fréquentes d’un refus</h3>
        <ul>
          <li>Action incomplète (ex : inscription non terminée).</li>
          <li>Déjà fait auparavant (doublon).</li>
          <li>Informations incohérentes / fausses.</li>
          <li>Utilisation de VPN/proxy ou comportement suspect.</li>
        </ul>

        <h2>4) Solde et échanges</h2>
        <p>
          Les points apparaissent dans ton solde uniquement quand ils sont <strong>validés</strong>.
          Ensuite tu peux faire une demande d’échange (ex : PayPal).
        </p>

        <h3>Règles simples (pour protéger tout le monde)</h3>
        <ul>
          <li>Un délai peut s’appliquer pour contrôler les demandes.</li>
          <li>En cas de doute (activité anormale), une demande peut être mise “en revue” avant paiement.</li>
          <li>Si un partenaire annule un gain (rare, mais possible), le solde peut être ajusté.</li>
        </ul>

        <h2>5) Ce qui est interdit</h2>
        <p>Pour rester fair-play et éviter les abus :</p>
        <ul>
          <li>Multi-comptes / création de comptes “en série”.</li>
          <li><strong>VPN / proxy / IP masquée</strong> (outil qui change ou cache ta localisation).</li>
          <li>Automatisation (bots, scripts, auto-clickers, etc.).</li>
          <li>Fausse identité / infos inventées pour valider une offre.</li>
          <li>Tenter de forcer un paiement sur une action refusée par le partenaire.</li>
        </ul>

        <h2>6) Suspension / fermeture de compte</h2>
        <p>
          On peut suspendre ou fermer un compte en cas d’abus, de tentative de fraude,
          ou si on pense que le site est utilisé contre son objectif.
        </p>

        <h2>7) Responsabilité</h2>
        <ul>
          <li>Les offres appartiennent à des partenaires externes : ils peuvent changer leurs règles ou leurs pages.</li>
          <li>Valyro fait au mieux pour suivre, mais ne peut pas garantir une validation automatique.</li>
          <li>En cas de bug, on corrige le plus vite possible, mais sans promesse de gains “assurés”.</li>
        </ul>

        <h2>8) Données personnelles</h2>
        <p>
          Tout est expliqué dans la page
          <a class="link" href="{{ route('legal.confidentialite') }}">Confidentialité</a>.
        </p>

        <h2>9) Contact</h2>
        <p>
          Une question ? Écris-nous :
          <a class="link" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
          ou via la page
          <a class="link" href="{{ route('legal.support') }}">Support</a>.
        </p>

        <h2>10) Loi applicable</h2>
        <p>
          Ces conditions sont soumises au droit français.
        </p>
      </div>
    </div>

    <div style="margin-top: 1rem; display:flex; justify-content:flex-end;">
      <a class="btn-back" href="{{ route('home') }}">← Retour à l’accueil</a>
    </div>

  </div>
</div>
@endsection
