{{-- resources/views/legal/support.blade.php --}}
@extends('layouts.app')

@section('content')
<style>
  :root{
    --page:#0B0A18; --page-2:#141127;
    --surface:rgba(255,255,255,.06); --surface-2:rgba(255,255,255,.09);
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
    color: rgba(255,255,255,.72); font-size: .85rem;
  }
  .dot{ width:.5rem; height:.5rem; border-radius:999px; display:inline-block; background: var(--gold); }

  h1{ font-size: 1.75rem; line-height: 1.2; font-weight: 900; }
  @media (min-width: 640px){ h1{ font-size: 2.15rem; } }
  h2{ margin-top: 1.5rem; font-size: 1.2rem; font-weight: 900; }

  p, li{ color: var(--muted); }
  p{ margin-top: .65rem; }
  ul{ margin-top: .65rem; padding-left: 1.1rem; list-style: disc; }
  li{ margin-top: .35rem; }

  .grid{
    display:grid;
    gap: 1rem;
    margin-top: 1.25rem;
  }
  @media (min-width: 768px){
    .grid{ grid-template-columns: 1.1fr .9fr; }
  }

  .panel{
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    border-radius: 1rem;
    padding: 1rem;
  }

  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
    border-radius: 999px;
    padding: .75rem 1.05rem;
    font-weight: 800;
    border: 1px solid rgba(255,255,255,.10);
    background: linear-gradient(135deg, rgba(245,197,66,1), rgba(225,29,72,.92));
    color: rgba(12,10,22,.92);
    transition: transform .15s ease, filter .2s ease, box-shadow .2s ease;
    user-select:none;
    -webkit-tap-highlight-color: transparent;
  }
  .btn:hover{ filter: brightness(1.03); transform: translateY(-1px); box-shadow: 0 18px 44px rgba(225,29,72,.20); }
  .btn:focus{ outline:none; box-shadow: var(--ring); }

  a.link{ color: rgba(245,197,66,.95); text-decoration: underline; text-underline-offset: 3px; }
  a.link:hover{ filter: brightness(1.05); }
  .focus-ring:focus{ outline:none; box-shadow: var(--ring); border-radius: .5rem; }

  details{ border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); border-radius: 1rem; padding: 1rem; }
  summary{ cursor:pointer; font-weight: 900; color: rgba(255,255,255,.92); }
  summary::-webkit-details-marker{ display:none; }
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
        <span>Support</span>
      </div>

      <a class="btn-back" href="{{ route('home') }}">← Retour à l’accueil</a>
    </div>

    <div class="card">
      <div class="inner">
        <div class="kicker"><span class="dot"></span> Support</div>

        <h1 class="mt-3">Besoin d’aide ?</h1>
        <p>
          Pour qu’on t’aide vite, le secret c’est simple : un message clair, et les bonnes infos.
          Comme ça, on peut retrouver ton dossier en quelques secondes.
        </p>

        <div class="grid">
          <div class="panel">
            <h2>Nous contacter</h2>
            <p>
              Email :
              <a class="link focus-ring" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
            </p>

            <p class="mt-3">
              Copie-colle ce modèle (ça fait gagner du temps à tout le monde) :
            </p>

            <ul>
              <li><strong>Objet :</strong> Problème de gain / échange</li>
              <li><strong>Ton compte :</strong> l’email utilisé sur Valyro</li>
              <li><strong>Offre :</strong> le nom que tu as vu sur Valyro</li>
              <li><strong>Date / heure :</strong> quand tu l’as faite</li>
              <li><strong>Ce que tu as fait :</strong> 2–3 phrases, pas plus</li>
              <li><strong>Capture d’écran :</strong> si tu en as une (facultatif mais utile)</li>
            </ul>

            <div class="mt-5">
              <a class="btn" href="mailto:{{ $supportEmail }}?subject=Support%20Valyro%20-%20Aide">
                ✉️ Écrire au support
              </a>
            </div>

            <p class="mt-4" style="color: rgba(255,255,255,.62); font-size:.92rem;">
              Petit conseil : pour les offres, évite les VPN et les bloqueurs trop agressifs.
              Ça peut empêcher le suivi et ralentir la validation.
            </p>
          </div>

          <div class="panel">
            <h2>Questions fréquentes</h2>

            <div class="mt-3 space-y-3">
              <details>
                <summary>Pourquoi mon gain reste “En attente” ?</summary>
                <p class="mt-2">
                  Parce que l’offre doit être vérifiée. Ça peut prendre un peu de temps (contrôle, délai de traitement).
                  Tant que ce n’est pas validé, on ne peut pas l’ajouter au solde.
                </p>
              </details>

              <details>
                <summary>Pourquoi un gain peut être “Refusé” ?</summary>
                <p class="mt-2">
                  Le plus courant : action incomplète, déjà faite auparavant, infos incohérentes,
                  ou conditions non respectées (pays, appareil, etc.).
                </p>
              </details>

              <details>
                <summary>Je suis sûr de l’avoir fait, vous pouvez le valider ?</summary>
                <p class="mt-2">
                  On peut vérifier ce qu’on a de notre côté, mais la validation finale vient du partenaire.
                  Si c’est refusé chez eux, on ne peut pas “forcer” le paiement.
                </p>
              </details>

              <details>
                <summary>Comment fonctionne un échange PayPal ?</summary>
                <p class="mt-2">
                  Quand ton solde est disponible, tu fais une demande d’échange.
                  On vérifie la demande (sécurité), puis on envoie sur ton email PayPal.
                </p>
              </details>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>
@endsection
