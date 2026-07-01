@extends('layout.main')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h4 class="mb-1">Ajouter des camions — {{ $transporteur->code }}</h4>
        <p class="text-muted mb-0">{{ $transporteur->nom }} {{ $transporteur->prenoms }}</p>
        <p class="text-muted small mb-0">Les camions du groupe PGF ne peuvent pas être attribués à un transporteur.</p>
      </div>
      <a href="{{ route('transporteurs.show', $transporteur) }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i>Retour à la liste
      </a>
    </div>

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <form method="POST" action="{{ route('transporteurs.camions.assigner', $transporteur) }}" id="formAjouterCamionsTransporteur">
      @csrf
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">Camions disponibles <span class="badge bg-label-primary">{{ $total_disponibles }}</span></h5>
          <div class="d-flex gap-2 flex-wrap">
            <input type="text" id="searchVehicule" class="form-control" placeholder="Rechercher par immatriculation..." style="width: 240px;" autocomplete="off">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnSelectAll">
              <i class="bx bx-check-square me-1"></i>Tout sélectionner
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDeselectAll">
              <i class="bx bx-square me-1"></i>Tout désélectionner
            </button>
          </div>
        </div>

        <div class="table-responsive text-nowrap">
          <table class="table table-hover mb-0" id="tableVehiculesDisponibles">
            <thead>
              <tr>
                <th style="width: 48px;">
                  <input type="checkbox" class="form-check-input" id="checkAll" title="Tout sélectionner">
                </th>
                <th>#</th>
                <th>Matricule</th>
                <th>Type</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              @forelse($lignes as $index => $ligne)
                @php
                  $typeVehicule = strtolower($ligne['type_vehicule'] ?? '');
                  $selectable = $ligne['selectable'];
                @endphp
                <tr
                  class="vehicule-row {{ !$selectable ? 'table-light' : '' }}"
                  data-matricule="{{ strtolower($ligne['matricule_vehicule']) }}"
                >
                  <td>
                    @if($selectable)
                      <input
                        type="checkbox"
                        class="form-check-input vehicule-checkbox"
                        name="vehicule_ids[]"
                        value="{{ $ligne['vehicule_id'] }}"
                        id="vehicule_{{ $ligne['vehicule_id'] }}"
                      >
                      <input type="hidden" name="matricules[{{ $ligne['vehicule_id'] }}]" value="{{ $ligne['matricule_vehicule'] }}">
                    @else
                      <input type="checkbox" class="form-check-input" disabled>
                    @endif
                  </td>
                  <td>{{ $index + 1 }}</td>
                  <td>
                    <label for="vehicule_{{ $ligne['vehicule_id'] }}" class="mb-0 {{ $selectable ? 'cursor-pointer' : '' }}">
                      <strong>{{ $ligne['matricule_vehicule'] ?: '—' }}</strong>
                    </label>
                  </td>
                  <td>
                    @if($typeVehicule === 'voiture')
                      <i class="bx bxs-truck text-primary"></i> Camion
                    @elseif($typeVehicule === 'moto')
                      <i class="bx bx-cycling text-success"></i> Moto
                    @else
                      {{ $ligne['type_vehicule'] ?: '—' }}
                    @endif
                  </td>
                  <td>
                    @if($ligne['est_pgf'])
                      <span class="badge bg-label-danger">PGF</span>
                    @elseif($ligne['autre_transporteur'])
                      <span class="badge bg-label-warning">{{ $ligne['autre_transporteur']->code }}</span>
                    @elseif($ligne['deja_associe'])
                      <span class="badge bg-label-success">Déjà associé</span>
                    @else
                      <span class="text-muted">Disponible</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">Aucun camion disponible à ajouter.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted" id="selectionCount">0 camion sélectionné</span>
        <div class="d-flex gap-2">
          <a href="{{ route('transporteurs.show', $transporteur) }}" class="btn btn-secondary">Annuler</a>
          <button type="button" class="btn btn-primary" id="btnValider" disabled>
            <i class="bx bx-check me-1"></i>Valider la sélection
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalAucuneSelection" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bx bx-error-circle me-2"></i>Sélection requise</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Veuillez sélectionner au moins un camion avant de valider.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Compris</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalConfirmerAjout" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-check-circle me-2"></i>Confirmer l'ajout des camions
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">
          Vous allez associer
          <strong id="modalConfirmerCount">0</strong>
          <span id="modalConfirmerLabelCamion">camion</span>
          au transporteur suivant :
        </p>
        <div class="alert alert-light border mb-0">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-label-primary">{{ $transporteur->code }}</span>
            <strong>{{ $transporteur->nom }} {{ $transporteur->prenoms }}</strong>
          </div>
        </div>
        <p class="text-muted small mb-0 mt-3">
          <i class="bx bx-info-circle me-1"></i>Les camions du groupe PGF ne peuvent pas être attribués.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-primary" id="btnConfirmerAjout">
          <i class="bx bx-check me-1"></i>Confirmer
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  .cursor-pointer { cursor: pointer; }
  .vehicule-row.d-none { display: none !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var searchInput = document.getElementById('searchVehicule');
  var checkAll = document.getElementById('checkAll');
  var btnSelectAll = document.getElementById('btnSelectAll');
  var btnDeselectAll = document.getElementById('btnDeselectAll');
  var btnValider = document.getElementById('btnValider');
  var selectionCount = document.getElementById('selectionCount');
  var form = document.getElementById('formAjouterCamionsTransporteur');
  var modalAucuneSelection = document.getElementById('modalAucuneSelection');
  var modalConfirmerAjout = document.getElementById('modalConfirmerAjout');
  var modalConfirmerCount = document.getElementById('modalConfirmerCount');
  var modalConfirmerLabelCamion = document.getElementById('modalConfirmerLabelCamion');
  var btnConfirmerAjout = document.getElementById('btnConfirmerAjout');

  function selectableCheckboxes() {
    return Array.from(document.querySelectorAll('.vehicule-checkbox'));
  }

  function visibleSelectableCheckboxes() {
    return Array.from(document.querySelectorAll('.vehicule-row:not(.d-none) .vehicule-checkbox'));
  }

  function updateSelectionState() {
    var checked = selectableCheckboxes().filter(function(cb) { return cb.checked; });
    var count = checked.length;
    selectionCount.textContent = count + (count > 1 ? ' camions sélectionnés' : ' camion sélectionné');
    btnValider.disabled = count === 0;

    var visible = visibleSelectableCheckboxes();
    if (checkAll) {
      checkAll.checked = visible.length > 0 && visible.every(function(cb) { return cb.checked; });
      checkAll.indeterminate = visible.some(function(cb) { return cb.checked; }) && !checkAll.checked;
    }
  }

  function filterRows() {
    var term = (searchInput?.value || '').toLowerCase().trim();
    document.querySelectorAll('.vehicule-row').forEach(function(row) {
      var matricule = row.dataset.matricule || '';
      row.classList.toggle('d-none', term !== '' && !matricule.includes(term));
    });
    updateSelectionState();
  }

  selectableCheckboxes().forEach(function(cb) {
    cb.addEventListener('change', updateSelectionState);
  });

  if (checkAll) {
    checkAll.addEventListener('change', function() {
      visibleSelectableCheckboxes().forEach(function(cb) {
        cb.checked = checkAll.checked;
      });
      updateSelectionState();
    });
  }

  if (btnSelectAll) {
    btnSelectAll.addEventListener('click', function() {
      visibleSelectableCheckboxes().forEach(function(cb) { cb.checked = true; });
      updateSelectionState();
    });
  }

  if (btnDeselectAll) {
    btnDeselectAll.addEventListener('click', function() {
      selectableCheckboxes().forEach(function(cb) { cb.checked = false; });
      updateSelectionState();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterRows);
  }

  if (btnValider) {
    btnValider.addEventListener('click', function() {
      var count = selectableCheckboxes().filter(function(cb) { return cb.checked; }).length;
      if (count === 0) {
        if (modalAucuneSelection) {
          bootstrap.Modal.getOrCreateInstance(modalAucuneSelection).show();
        }
        return;
      }
      if (modalConfirmerCount) {
        modalConfirmerCount.textContent = count;
      }
      if (modalConfirmerLabelCamion) {
        modalConfirmerLabelCamion.textContent = count > 1 ? 'camions' : 'camion';
      }
      if (modalConfirmerAjout) {
        bootstrap.Modal.getOrCreateInstance(modalConfirmerAjout).show();
      }
    });
  }

  if (btnConfirmerAjout && form) {
    btnConfirmerAjout.addEventListener('click', function() {
      form.submit();
    });
  }

  if (form) {
    form.addEventListener('submit', function(e) {
      var count = selectableCheckboxes().filter(function(cb) { return cb.checked; }).length;
      if (count === 0) {
        e.preventDefault();
        if (modalAucuneSelection) {
          bootstrap.Modal.getOrCreateInstance(modalAucuneSelection).show();
        }
      }
    });
  }

  updateSelectionState();
});
</script>
@endsection
