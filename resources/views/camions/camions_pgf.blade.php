@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Camions PGF</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAssignerCamion">
        <i class="bx bx-plus me-1"></i>Ajouter un camion
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des camions du groupe PGF</h5>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control" placeholder="Rechercher..." value="{{ request('q') }}" style="width: 200px;">
          <button type="submit" class="btn btn-outline-primary"><i class="bx bx-search"></i></button>
        </form>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Matricule</th>
              <th>Type</th>
              <th>Date d'ajout</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($camions_pgf as $index => $v)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $v['vehicules_id'], 'matricule' => $v['matricule_vehicule']]) }}">
                    <strong>{{ $v['matricule_vehicule'] ?? '-' }}</strong>
                  </a>
                </td>
                <td>
                  @php $typeVehicule = strtolower($v['type_vehicule'] ?? ''); @endphp
                  @if($typeVehicule === 'voiture')
                    <i class="bx bxs-truck text-primary"></i> Camion
                  @elseif($typeVehicule === 'moto')
                    <i class="bx bx-cycling text-success"></i> Moto
                  @else
                    {{ $v['type_vehicule'] ?? '-' }}
                  @endif
                </td>
                <td>{{ $v['created_at'] ?? '-' }}</td>
                <td>
                  <form action="{{ route('camions.retirer_groupe', ['vehicule_id' => $v['vehicules_id']]) }}" method="POST" class="d-inline" onsubmit="return confirm('Retirer ce camion du groupe PGF ?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="groupe_id" value="{{ $groupe_pgf->id }}">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bx bx-trash"></i> Retirer
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center">Aucun camion dans le groupe PGF</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Résumé</h5>
        <p><strong>Nombre de camions PGF:</strong> <span class="badge bg-primary">{{ count($camions_pgf) }}</span></p>
      </div>
    </div>
  </div>
</div>

<!-- Modal Assigner Camion -->
<div class="modal fade" id="modalAssignerCamion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-plus me-2"></i>Ajouter un camion au groupe PGF</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('camions.assigner_groupe') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Sélectionner un véhicule</label>
            <select name="vehicule_id" class="form-select" required id="selectVehicule">
              <option value="">-- Choisir un véhicule --</option>
              @foreach($all_vehicules as $v)
                <option value="{{ $v['vehicules_id'] }}" data-matricule="{{ $v['matricule_vehicule'] }}">
                  {{ $v['matricule_vehicule'] }} ({{ $v['type_vehicule'] ?? '-' }})
                </option>
              @endforeach
            </select>
            <input type="hidden" name="matricule_vehicule" id="matriculeVehicule">
          </div>
          <input type="hidden" name="groupe_id" value="{{ $groupe_pgf->id }}">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectVehicule = document.getElementById('selectVehicule');
  const matriculeInput = document.getElementById('matriculeVehicule');
  
  if (selectVehicule && matriculeInput) {
    selectVehicule.addEventListener('change', function() {
      const selected = this.options[this.selectedIndex];
      matriculeInput.value = selected.dataset.matricule || '';
    });
  }
});
</script>
@endsection
