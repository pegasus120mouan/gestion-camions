@extends('layout.main')
@section('title', 'Paramètres')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
      <h4 class="mb-1">Paramètres</h4>
      <p class="text-muted mb-0">Réglages de l'application.</p>
    </div>

    @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger', 'info' => 'info'] as $cle => $classe)
      @if(session($cle))
        <div class="alert alert-{{ $classe }} alert-dismissible fade show" role="alert">
          {{ session($cle) }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
    @endforeach

    @if(session('validation_echecs'))
      <div class="alert alert-danger" role="alert">
        <strong>{{ session('validation_echecs_titre', 'Tickets non validés :') }}</strong>
        <ul class="mb-0 mt-2">
          @foreach(session('validation_echecs') as $echec)
            <li>{{ $echec }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="card">
      <div class="card-header">
        <h5 class="mb-1"><i class="bx bx-check-double me-2"></i>Validation des tickets en attente</h5>
        <small class="text-muted">
          Valide tous les tickets en attente du type choisi, avec le même traitement qu'une validation unitaire
          (prix, montant, fiche agent, transporteur). Camions PGF : la fiche de sortie correspondante est associée
          automatiquement si elle existe, sinon le ticket est validé sans fiche.
        </small>
      </div>
      <div class="card-body">
        <div class="row g-4">
          @foreach([
            'professionnel' => ['libelle' => 'Tickets professionnels', 'couleur' => 'primary'],
            'particulier' => ['libelle' => 'Tickets particuliers', 'couleur' => 'success'],
          ] as $type => $info)
            <div class="col-md-6">
              <div class="border rounded p-4 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0">{{ $info['libelle'] }}</h6>
                  <span class="badge rounded-pill bg-danger">{{ $countsEnAttente[$type] ?? 0 }} en attente</span>
                </div>
                <form method="POST" action="{{ route('parametres.tickets.valider_tout') }}" class="mt-auto js-valider-tout"
                  data-libelle="{{ mb_strtolower($info['libelle']) }} en attente" data-nombre="{{ $countsEnAttente[$type] ?? 0 }}"
                  data-titre="Confirmer la validation" data-verbe="valider" data-action="Valider" data-encours="Validation en cours…"
                  data-note="Le prix, le montant, la fiche agent et le transporteur seront calculés pour chaque ticket.">
                  @csrf
                  <input type="hidden" name="type" value="{{ $type }}" />
                  <button type="submit" class="btn btn-{{ $info['couleur'] }} w-100" @disabled(($countsEnAttente[$type] ?? 0) === 0)>
                    <i class="bx bx-check-double me-1"></i>Valider tous les {{ mb_strtolower($info['libelle']) }}
                  </button>
                </form>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-1"><i class="bx bx-download me-2"></i>Déchargement des fiches de sortie</h5>
        <small class="text-muted">
          Décharge toutes les fiches de sortie non déchargées. Si un ticket Unipalm en attente correspond
          (véhicule, agent, pont, usine), la fiche est déchargée avec ce ticket (camion PGF : ticket validé et stock déduit).
          Sinon, la fiche est déchargée à la date du jour avec son poids, sans déduction de stock.
        </small>
      </div>
      <div class="card-body">
        <div class="border rounded p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <h6 class="mb-0">Fiches de sortie non déchargées</h6>
            <span class="badge rounded-pill bg-warning">{{ $fichesNonDechargees }} en attente</span>
          </div>
          <form method="POST" action="{{ route('parametres.fiches.decharger_tout') }}" class="js-valider-tout"
            data-libelle="fiche(s) de sortie non déchargée(s)" data-nombre="{{ $fichesNonDechargees }}"
            data-titre="Confirmer le déchargement" data-verbe="décharger" data-action="Décharger" data-encours="Déchargement en cours…"
            data-note="Les fiches sans ticket correspondant seront déchargées à la date du jour, sans déduction de stock.">
            @csrf
            <button type="submit" class="btn btn-warning" @disabled($fichesNonDechargees === 0)>
              <i class="bx bx-download me-1"></i>Décharger toutes les fiches
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="card mt-4 border border-danger">
      <div class="card-header">
        <h5 class="mb-1 text-danger"><i class="bx bx-trash me-2"></i>Remise à zéro</h5>
        <small class="text-muted">
          Suppression définitive des données locales gest-camions. Les ponts eux-mêmes viennent d'Unipalm et restent dans la liste.
        </small>
      </div>
      <div class="card-body">
        <div class="row g-4">
          @foreach([
            'stocks' => [
              'titre' => 'Stocks des ponts',
              'description' => 'Supprime tous les stocks et leurs entrées : le « Stock disponible » de chaque pont revient à 0 kg.',
              'libelle' => 'stock(s) et entrée(s) de stock des ponts',
              'icone' => 'bx-package',
            ],
            'ponts' => [
              'titre' => 'Soldes des ponts',
              'description' => 'Supprime les approvisionnements et dépenses des ponts : avec les stocks vidés, le « Solde » revient à 0 FCFA.',
              'libelle' => 'approvisionnement(s) et dépense(s) des ponts',
              'icone' => 'bx-wallet',
            ],
            'parcs' => [
              'titre' => 'Parcs',
              'description' => 'Supprime tous les parcs. Les stocks et fiches de sortie liés perdent simplement leur parc.',
              'libelle' => 'parc(s)',
              'icone' => 'bx-map-pin',
            ],
          ] as $cible => $info)
            <div class="col-md-4">
              <div class="border rounded p-4 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0"><i class="bx {{ $info['icone'] }} me-1"></i>{{ $info['titre'] }}</h6>
                  <span class="badge rounded-pill bg-label-danger">{{ $remiseAZero[$cible] }}</span>
                </div>
                <p class="text-muted small mb-3">{{ $info['description'] }}</p>
                <form method="POST" action="{{ route('parametres.vider') }}" class="mt-auto js-valider-tout"
                  data-libelle="{{ $info['libelle'] }}" data-nombre="{{ $remiseAZero[$cible] }}"
                  data-titre="Confirmer la suppression" data-verbe="supprimer" data-action="Vider" data-encours="Suppression en cours…"
                  data-note="{{ $info['description'] }}">
                  @csrf
                  <input type="hidden" name="cible" value="{{ $cible }}" />
                  <button type="submit" class="btn btn-danger w-100" @disabled($remiseAZero[$cible] === 0)>
                    <i class="bx bx-trash me-1"></i>Vider : {{ mb_strtolower($info['titre']) }}
                  </button>
                </form>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalConfirmValiderTout" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-5">
        <div class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle bg-label-warning"
          style="width: 72px; height: 72px;">
          <i class="bx bx-error text-warning" style="font-size: 2.5rem;"></i>
        </div>
        <h4 class="mb-2" id="confirmValiderToutTitre">Confirmer la validation</h4>
        <p class="text-muted mb-4">
          Vous êtes sur le point de <span id="confirmValiderToutVerbe">valider</span>
          <strong class="text-body" id="confirmValiderToutNombre">0</strong>
          <span id="confirmValiderToutLibelle"></span>.
        </p>

        <div class="alert alert-warning d-flex align-items-start text-start mb-4" role="alert">
          <i class="bx bx-info-circle me-2 mt-1"></i>
          <div>
            <span id="confirmValiderToutNote"></span>
            <strong>Cette action est irréversible.</strong>
          </div>
        </div>

        <div class="d-flex justify-content-center gap-3">
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Annuler</button>
          <button type="button" class="btn btn-primary px-4" id="confirmValiderToutBtn"></button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-scripts')
<script>
(function () {
  var modalEl = document.getElementById('modalConfirmValiderTout');
  var confirmBtn = document.getElementById('confirmValiderToutBtn');
  var formEnCours = null;

  document.querySelectorAll('form.js-valider-tout').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.dataset.confirme === '1') {
        return;
      }
      e.preventDefault();
      formEnCours = form;

      document.getElementById('confirmValiderToutTitre').textContent = form.dataset.titre;
      document.getElementById('confirmValiderToutVerbe').textContent = form.dataset.verbe;
      document.getElementById('confirmValiderToutNombre').textContent = form.dataset.nombre;
      document.getElementById('confirmValiderToutLibelle').textContent = form.dataset.libelle;
      document.getElementById('confirmValiderToutNote').textContent = form.dataset.note + ' ';

      var boutonForm = form.querySelector('button[type="submit"]');
      var classeBouton = ['btn-success', 'btn-warning', 'btn-danger'].find(function (c) {
        return boutonForm.classList.contains(c);
      }) || 'btn-primary';
      confirmBtn.classList.remove('btn-primary', 'btn-success', 'btn-warning', 'btn-danger');
      confirmBtn.classList.add(classeBouton);
      confirmBtn.innerHTML = boutonForm.querySelector('i').outerHTML + form.dataset.action;

      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
  });

  confirmBtn.addEventListener('click', function () {
    if (!formEnCours) {
      return;
    }
    var enCours = '<span class="spinner-border spinner-border-sm me-1"></span>' + formEnCours.dataset.encours;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = enCours;
    modalEl.querySelector('[data-bs-dismiss="modal"]').disabled = true;

    formEnCours.querySelector('button[type="submit"]').innerHTML = enCours;
    document.querySelectorAll('form.js-valider-tout button').forEach(function (b) { b.disabled = true; });

    formEnCours.dataset.confirme = '1';
    formEnCours.submit();
  });
})();

@if(session('play_validation_sound'))
new Audio(@json(asset('sounds/validation.wav'))).play().catch(function () {});
@endif
</script>
@endsection
