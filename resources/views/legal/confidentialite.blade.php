{{-- resources/views/legal/confidentialite.blade.php --}}
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

  .wrap{ padding: 2.5rem 1rem; }
  .container{ max-width: 980px; margin: 0 auto; }

  .topbar{
    display:flex; align-items:center; justify-content:space-between; gap:.75rem;
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
    overflow:hidden;
  }
  .inner{ padding: 1.5rem; }
  @media (min-width: 640px){ .inner{ padding: 2rem; } }

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
  h3{ margin-top: 1.1rem; font-size: 1.02rem; font-weight: 900; }

  p, li{ color: var(--muted); }
  p{ margin-top: .65rem; }
  ul{ margin-top: .65rem; padding-left: 1.1rem; list-style: disc; }
  li{ margin-top: .35rem; }

  .sep{
    height: 1px; width: 100%;
    background: linear-gradient(90deg, transparent, rgba(124,58,237,.25), rgba(245,197,66,.18), transparent);
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

  a.link{ color: rgba(245,197,66,.95); text-decoration: underline; text-underline-offset: 3px; }
  a.link:hover{ filter: brightness(1.05); }

  .small{ font-size: .92rem; color: rgba(255,255,255,.65); }
</style>

@php
  $supportEmail = config('app.support_email', 'contact@valyro.fr');
@endphp

<div class="wrap">
  <div class="container">

    <div class="topbar">
      <div class="crumbs">
        <a href="{{ route('home') }}">Accueil</a>
        <span>›</span>
        <span>Confidentialité</span>
      </div>

      <a class="btn-back" href="{{ route('home') }}">← Retour à l’accueil</a>
    </div>

    <div class="card">
      <div class="inner">
        <div class="kicker"><span class="dot"></span> Données & confidentialité</div>

        <h1 class="mt-3">Politique de confidentialité</h1>
        <p class="small">Mise à jour : {{ now()->format('d/m/Y') }}.</p>

        <div class="sep"></div>

        <p>
          Ici, on t’explique clairement ce qu’on garde, pourquoi on le garde,
          et ce que tu peux demander.
          On fait simple : on prend le nécessaire, pas plus.
        </p>

        <div class="notice">
          Petit point important : pendant une offre, si tu utilises un VPN ou un bloqueur très agressif,
          ça peut empêcher l’attribution du gain. Résultat : tu peux faire l’action… et elle ne remonte pas correctement.
        </div>

        <h2>1) Qui gère tes données ?</h2>
        <p>
          Valyro est responsable du traitement des données pour le site.
          Contact : <a class="link" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
        </p>

        <h2>2) Ce qu’on collecte (et à quoi ça sert)</h2>

        <h3>Ton compte</h3>
        <ul>
          <li><strong>Email</strong> : connexion + sécurité + messages importants.</li>
          <li><strong>Mot de passe</strong> : stocké de manière sécurisée (jamais en clair).</li>
          <li><strong>Infos de profil</strong> : uniquement si tu en ajoutes (optionnel).</li>
        </ul>

        <h3>Ton solde et ton historique</h3>
        <ul>
          <li>On garde l’historique de tes gains (en attente / validé / refusé) et de tes demandes d’échange.</li>
          <li>Ça sert à afficher ton suivi, traiter tes demandes, et éviter les abus.</li>
        </ul>

        <h3>Infos techniques (sécurité)</h3>
        <ul>
          <li>Comme la plupart des sites : on peut enregistrer des infos techniques (ex : navigateur, date/heure, adresse IP).</li>
          <li>But : sécurité, anti-abus, et résolution de problèmes quand il y a un litige.</li>
        </ul>

        <h2>3) Cookies</h2>
        <p>
          Le site utilise des cookies essentiels pour fonctionner (connexion, sécurité).
          Les partenaires d’offres utilisent aussi des cookies ou outils similaires
          pour savoir si une action vient bien de ton compte.
        </p>
        <p class="small">
          Conseil simple : pendant une offre, évite de tout bloquer, sinon tu risques de perdre l’attribution.
        </p>

        <h2>4) Partage des données</h2>
        <ul>
          <li><strong>Prestataires techniques</strong> (hébergement, emails) : uniquement le nécessaire.</li>
          <li><strong>Partenaires d’offres</strong> : uniquement ce qu’il faut pour attribuer l’action (pas tes infos perso “inutiles”).</li>
          <li><strong>Obligations légales</strong> : si la loi nous y oblige.</li>
        </ul>

        <h2>5) Durée de conservation</h2>
        <p>
          On garde les données tant qu’elles sont utiles au service (suivi, sécurité, échanges).
          Certaines données peuvent être conservées plus longtemps si la loi l’impose.
        </p>

        <h2>6) Tes droits</h2>
        <p>
          Tu peux demander : accès, correction, ou suppression quand c’est possible.
          Écris-nous à : <a class="link" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
        </p>
        <p class="small">
          Si tu demandes une suppression, indique l’email du compte.
          On te dira ce qu’on peut supprimer tout de suite et ce qu’on doit garder (obligations légales).
        </p>

        <h2>7) Mise à jour de cette page</h2>
        <p>
          Si Valyro évolue, on mettra à jour cette page.
          La date en haut changera.
        </p>

        <p class="mt-6 small">
          Liens utiles :
          <a class="link" href="{{ route('legal.conditions') }}">Conditions</a> •
          <a class="link" href="{{ route('legal.support') }}">Support</a>
        </p>
      </div>
    </div>

    <div style="margin-top: 1rem; display:flex; justify-content:flex-end;">
      <a class="btn-back" href="{{ route('home') }}">← Retour à l’accueil</a>
    </div>

  </div>
</div>
@endsection
