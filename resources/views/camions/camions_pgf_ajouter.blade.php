@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h4 class="mb-1">Ajouter des camions au groupe PGF</h4>
        <p class="text-muted mb-0">Sélectionnez un ou plusieurs camions puis validez.</p>
      </div>
      <a href="{{ route('camions.camions_pgf') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i>Retour à la liste PGF
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

    <form method="POST" action="{{ route('camions.assigner_groupe_bulk') }}" id="formAjouterCamionsPgf">
      @csrf
      <input type="hidden" name="groupe_id" value="{{ $groupe_pgf->id }}">

      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">Camions disponibles <span class="badge bg-label-primary">{{ $total_disponibles }}</span></h5>
          <div class="d-flex gap-2 flex-wrap">
            <input type="text" id="searchVehicule" class="form-control" placeholder="Rechercher par immatriculation..." style="width: 240px;" autocomplete="off">
            <button type="button" class="btn btn-outline-primary" id="btnSelectAll">
              <i class="bx bx-check-square me-1"></i>Tout sélectionner
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnDeselectAll">
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
              </tr>
            </thead>
            <tbody>
              @forelse($vehicules_disponibles as $index => $v)
                @php
                  $vehiculeId = (int) ($v['vehicules_id'] ?? 0);
                  $matricule = $v['matricule_vehicule'] ?? '';
                  $typeVehicule = strtolower($v['type_vehicule'] ?? '');
                @endphp
                <tr class="vehicule-row" data-matricule="{{ strtolower($matricule) }}" data-type="{{ $typeVehicule }}">
                  <td>
                    <input
                      type="checkbox"
                      class="form-check-input vehicule-checkbox"
                      name="vehicule_ids[]"
                      value="{{ $vehiculeId }}"
                      id="vehicule_{{ $vehiculeId }}"
                    >
                    <input type="hidden" name="matricules[{{ $vehiculeId }}]" value="{{ $matricule }}">
                  </td>
                  <td>{{ $index + 1 }}</td>
                  <td>
                    <label for="vehicule_{{ $vehiculeId }}" class="mb-0 cursor-pointer">
                      <strong>{{ $matricule ?: '-' }}</strong>
                    </label>
                  </td>
                  <td>
                    @if($typeVehicule === 'voiture')
                      <i class="bx bxs-truck text-primary"></i> Camion
                    @elseif($typeVehicule === 'moto')
                      <i class="bx bx-cycling text-success"></i> Moto
                    @else
                      {{ $v['type_vehicule'] ?? '-' }}
                    @endif
                  </td>
                </tr>
              @empty
                <tr id="rowAucunVehicule">
                  <td colspan="4" class="text-center py-4">
                    Tous les camions sont déjà dans le groupe PGF.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted" id="selectionCount">0 camion sélectionné</span>
        <div class="d-flex gap-2">
          <a href="{{ route('camions.camions_pgf') }}" class="btn btn-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary" id="btnValider" disabled>
            <i class="bx bx-check me-1"></i>Valider la sélection
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
  .cursor-pointer { cursor: pointer; }
  .vehicule-row.d-none { display: none !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchVehicule');
  const checkAll = document.getElementById('checkAll');
  const btnSelectAll = document.getElementById('btnSelectAll');
  const btnDeselectAll = document.getElementById('btnDeselectAll');
  const btnValider = document.getElementById('btnValider');
  const selectionCount = document.getElementById('selectionCount');
  const form = document.getElementById('formAjouterCamionsPgf');

  function visibleCheckboxes() {
    return Array.from(document.querySelectorAll('.vehicule-row:not(.d-none) .vehicule-checkbox'));
  }

  function allCheckboxes() {
    return Array.from(document.querySelectorAll('.vehicule-checkbox'));
  }

  function updateSelectionState() {
    const checked = allCheckboxes().filter(function(cb) { return cb.checked; });
    const count = checked.length;
    selectionCount.textContent = count + (count > 1 ? ' camions sélectionnés' : ' camion sélectionné');
    btnValider.disabled = count === 0;

    const visible = visibleCheckboxes();
    if (checkAll) {
      checkAll.checked = visible.length > 0 && visible.every(function(cb) { return cb.checked; });
      checkAll.indeterminate = visible.some(function(cb) { return cb.checked; }) && !checkAll.checked;
    }
  }

  function filterRows() {
    const term = (searchInput?.value || '').toLowerCase().trim();
    document.querySelectorAll('.vehicule-row').forEach(function(row) {
      const matricule = row.dataset.matricule || '';
      const type = row.dataset.type || '';
      const match = term === '' || matricule.includes(term) || type.includes(term);
      row.classList.toggle('d-none', !match);
    });
    updateSelectionState();
  }

  allCheckboxes().forEach(function(cb) {
    cb.addEventListener('change', updateSelectionState);
  });

  if (checkAll) {
    checkAll.addEventListener('change', function() {
      visibleCheckboxes().forEach(function(cb) {
        cb.checked = checkAll.checked;
      });
      updateSelectionState();
    });
  }

  if (btnSelectAll) {
    btnSelectAll.addEventListener('click', function() {
      visibleCheckboxes().forEach(function(cb) { cb.checked = true; });
      updateSelectionState();
    });
  }

  if (btnDeselectAll) {
    btnDeselectAll.addEventListener('click', function() {
      allCheckboxes().forEach(function(cb) { cb.checked = false; });
      updateSelectionState();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterRows);
  }

  if (form) {
    form.addEventListener('submit', function(e) {
      const count = allCheckboxes().filter(function(cb) { return cb.checked; }).length;
      if (count === 0) {
        e.preventDefault();
        alert('Sélectionnez au moins un camion.');
        return;
      }
      if (!confirm('Ajouter ' + count + ' camion(s) au groupe PGF ?')) {
        e.preventDefault();
      }
    });
  }

  updateSelectionState();
});
</script>
@endsection
