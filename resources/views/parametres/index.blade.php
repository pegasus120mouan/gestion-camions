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
        <strong>Tickets non validés :</strong>
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
                  data-libelle="{{ mb_strtolower($info['libelle']) }}" data-nombre="{{ $countsEnAttente[$type] ?? 0 }}">
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
        <h4 class="mb-2">Confirmer la validation</h4>
        <p class="text-muted mb-4">
          Vous êtes sur le point de valider
          <strong class="text-body" id="confirmValiderToutNombre">0</strong>
          <span id="confirmValiderToutLibelle"></span> en attente.
        </p>

        <div class="alert alert-warning d-flex align-items-start text-start mb-4" role="alert">
          <i class="bx bx-info-circle me-2 mt-1"></i>
          <div>
            Le prix, le montant, la fiche agent et le transporteur seront calculés pour chaque ticket.
            <strong>Cette action est irréversible.</strong>
          </div>
        </div>

        <div class="d-flex justify-content-center gap-3">
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Annuler</button>
          <button type="button" class="btn btn-primary px-4" id="confirmValiderToutBtn">
            <i class="bx bx-check-double me-1"></i>Valider
          </button>
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

      document.getElementById('confirmValiderToutNombre').textContent = form.dataset.nombre;
      document.getElementById('confirmValiderToutLibelle').textContent = form.dataset.libelle;
      var classeBouton = form.querySelector('button[type="submit"]').classList.contains('btn-success') ? 'btn-success' : 'btn-primary';
      confirmBtn.classList.remove('btn-primary', 'btn-success');
      confirmBtn.classList.add(classeBouton);

      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
  });

  confirmBtn.addEventListener('click', function () {
    if (!formEnCours) {
      return;
    }
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validation en cours…';
    modalEl.querySelector('[data-bs-dismiss="modal"]').disabled = true;

    var btn = formEnCours.querySelector('button[type="submit"]');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validation en cours…';
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
