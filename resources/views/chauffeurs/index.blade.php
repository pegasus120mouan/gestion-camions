@extends('layout.main')

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
  .modal .select2-container { z-index: 1056; }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Gestion Chauffeurs</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateChauffeur">
        <i class="bx bx-plus me-1"></i> Ajouter un chauffeur
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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

    <div class="row">
      <div class="col-lg-4">
        @include('chauffeurs._groupes_card', [
          'groupes' => $groupes,
          'totalChauffeurs' => $totalChauffeurs,
          'groupeFilter' => $groupeFilter,
          'search' => $search,
        ])
      </div>

      <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('chauffeurs.index') }}" class="row g-3 align-items-end">
          @if($groupeFilter)
            <input type="hidden" name="chauffeur_groupe_id" value="{{ $groupeFilter }}" />
          @endif
          <div class="col-md-8">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Nom, contact, matricule..." />
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('chauffeurs.index', $groupeFilter ? ['chauffeur_groupe_id' => $groupeFilter] : []) }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Groupe</th>
              <th>Nom</th>
              <th>Prénoms</th>
              <th>Contact</th>
              <th>Camion associé</th>
              <th class="text-end">Salaire</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($chauffeurs as $chauffeur)
              <tr>
                <td>
                  <span class="badge bg-label-info">{{ $chauffeur->groupe?->nom_groupe ?? '—' }}</span>
                </td>
                <td><strong>{{ $chauffeur->nom }}</strong></td>
                <td>{{ $chauffeur->prenoms }}</td>
                <td>{{ $chauffeur->contact ?: '—' }}</td>
                <td>
                  @if($chauffeur->matricule_vehicule)
                    <span class="badge bg-label-primary">{{ $chauffeur->matricule_vehicule }}</span>
                  @else
                    <span class="text-muted">Non assigné</span>
                  @endif
                </td>
                <td class="text-end fw-semibold">{{ number_format((float) $chauffeur->salaire, 0, ',', ' ') }} FCFA</td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditChauffeur{{ $chauffeur->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteChauffeur{{ $chauffeur->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">Aucun chauffeur enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $chauffeurs->links() }}
    </div>
      </div>
    </div>
  </div>
</div>

@php
  $vehiculeOptions = collect($vehicules ?? []);
@endphp

<div class="modal fade" id="modalCreateChauffeur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white">Ajouter un chauffeur</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('chauffeurs.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          @include('chauffeurs._form_fields', ['vehiculeOptions' => $vehiculeOptions, 'groupes' => $groupes, 'defaultGroupeId' => $defaultGroupeId])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach($chauffeurs as $chauffeur)
<div class="modal fade" id="modalEditChauffeur{{ $chauffeur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier le chauffeur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('chauffeurs.update', $chauffeur) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @include('chauffeurs._form_fields', ['chauffeur' => $chauffeur, 'vehiculeOptions' => $vehiculeOptions, 'groupes' => $groupes, 'defaultGroupeId' => $defaultGroupeId])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteChauffeur{{ $chauffeur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Supprimer <strong>{{ $chauffeur->nom }} {{ $chauffeur->prenoms }}</strong> ?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('chauffeurs.destroy', $chauffeur) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

@endsection

@section('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function() {
  var defaultGroupeId = @json($defaultGroupeId);
  var reopenCreateWithErrors = @json($errors->any() && old('_form') === 'create');
  var reopenEditFormKey = @json($errors->any() ? old('_form') : null);

  function syncVehiculeId($select) {
    var targetId = $select.data('vehicule-id-target');
    var $target = $('#' + targetId);
    var $opt = $select.find(':selected');
    $target.val($opt.attr('data-vehicule-id') || '');
  }

  function initChauffeurVehiculeSelect($modal) {
    $modal.find('.chauffeur-vehicule-select').each(function() {
      var $sel = $(this);
      if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.off('change.chauffeurVehicule');
        $sel.select2('destroy');
      }
      $sel.select2({
        theme: 'bootstrap-5',
        dropdownParent: $modal.find('.modal-body'),
        placeholder: '-- Aucun camion --',
        allowClear: true,
        width: '100%'
      });
      $sel.on('change.chauffeurVehicule', function() {
        syncVehiculeId($(this));
      });
      syncVehiculeId($sel);
    });
  }

  function resetCreateChauffeurForm() {
    var $modal = $('#modalCreateChauffeur');
    var $form = $modal.find('form');

    $form.find('[name="nom"]').val('');
    $form.find('[name="prenoms"]').val('');
    $form.find('[name="contact"]').val('');
    $form.find('[name="salaire"]').val('');
    $form.find('[name="chauffeur_groupe_id"]').val(String(defaultGroupeId));
    $form.find('[name="vehicule_id"]').val('');

    var $vehiculeSelect = $form.find('.chauffeur-vehicule-select');
    if ($vehiculeSelect.hasClass('select2-hidden-accessible')) {
      $vehiculeSelect.val(null).trigger('change');
    } else {
      $vehiculeSelect.val('');
    }
  }

  $(document).ready(function() {
    $('#modalCreateChauffeur').on('show.bs.modal', function() {
      if (!reopenCreateWithErrors) {
        resetCreateChauffeurForm();
      }
    });

    $(document).on('shown.bs.modal', '#modalCreateChauffeur, [id^="modalEditChauffeur"]', function() {
      initChauffeurVehiculeSelect($(this));
    });

    if (reopenCreateWithErrors) {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreateChauffeur')).show();
    } else if (reopenEditFormKey && String(reopenEditFormKey).indexOf('edit_') === 0) {
      var chauffeurId = String(reopenEditFormKey).replace('edit_', '');
      var editModal = document.getElementById('modalEditChauffeur' + chauffeurId);
      if (editModal) {
        bootstrap.Modal.getOrCreateInstance(editModal).show();
      }
    }
  });
})();
</script>
@endsection
