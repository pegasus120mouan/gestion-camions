@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('code_transporteurs.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux codes transporteurs
        </a>
        <h4 class="mb-0">
          <i class="bx bx-code-alt text-primary me-2"></i>
          {{ $code->nom }}
        </h4>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddVehicule">
        <i class="bx bx-plus me-1"></i>Attribuer un camion
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
        @foreach($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row">
      <!-- Informations -->
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label text-muted">ID</label>
              <p class="fw-bold mb-0">{{ $code->id }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Nom</label>
              <p class="fw-bold mb-0">{{ $code->nom }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Nombre de camions</label>
              <p class="fw-bold mb-0">
                <span class="badge bg-primary">{{ $code->vehicules->count() }}</span>
              </p>
            </div>
            <div class="mb-0">
              <label class="form-label text-muted">Date création</label>
              <p class="fw-bold mb-0">{{ $code->created_at->format('d-m-Y H:i') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des camions -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-car me-2"></i>Camions attribués</h5>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>ID Véhicule</th>
                  <th>Matricule</th>
                  <th>Date attribution</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($code->vehicules as $vehicule)
                  <tr>
                    <td><strong>{{ $vehicule->vehicule_id }}</strong></td>
                    <td>{{ $vehicule->matricule_vehicule }}</td>
                    <td>{{ $vehicule->created_at->format('d-m-Y H:i') }}</td>
                    <td class="text-center">
                      <form method="POST" action="{{ route('code_transporteurs.vehicules.remove', ['id' => $code->id, 'vehicule_id' => $vehicule->id]) }}" class="d-inline" onsubmit="return confirm('Retirer ce camion du code transporteur ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="bx bx-trash"></i> Retirer
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">Aucun camion attribué</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal Attribuer Camion -->
<div class="modal fade" id="modalAddVehicule" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('code_transporteurs.vehicules.add', $code->id) }}" id="formAddVehicule">
        @csrf
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Attribuer un camion</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Camion <span class="text-danger">*</span></label>
            <input type="text" id="searchCamion" class="form-control" placeholder="Tapez pour rechercher un camion..." autocomplete="off">
            <input type="hidden" name="vehicule_id" id="vehicule_id" value="">
            <input type="hidden" name="matricule_vehicule" id="matricule_vehicule" value="">
            <div id="autocompleteResults" class="list-group mt-1" style="max-height: 250px; overflow-y: auto; display: none;"></div>
            @if(empty($vehiculesDisponibles))
              <small class="text-muted">Tous les camions sont déjà attribués à ce code.</small>
            @endif
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" id="btnAttribuer" @if(empty($vehiculesDisponibles)) disabled @endif>Attribuer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const vehiculesDisponibles = @json($vehiculesDisponibles);
    const searchInput = document.getElementById('searchCamion');
    const resultsContainer = document.getElementById('autocompleteResults');
    const vehiculeIdInput = document.getElementById('vehicule_id');
    const matriculeInput = document.getElementById('matricule_vehicule');
    const btnAttribuer = document.getElementById('btnAttribuer');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        resultsContainer.innerHTML = '';
        
        if (query.length === 0) {
            resultsContainer.style.display = 'none';
            return;
        }

        const filtered = vehiculesDisponibles.filter(v => 
            (v.matricule_vehicule || '').toLowerCase().includes(query)
        );

        if (filtered.length === 0) {
            resultsContainer.innerHTML = '<div class="list-group-item text-muted">Aucun camion trouvé</div>';
            resultsContainer.style.display = 'block';
            return;
        }

        filtered.slice(0, 20).forEach(v => {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'list-group-item list-group-item-action';
            item.textContent = v.matricule_vehicule;
            item.dataset.id = v.vehicules_id;
            item.dataset.matricule = v.matricule_vehicule;
            item.addEventListener('click', function(e) {
                e.preventDefault();
                searchInput.value = this.dataset.matricule;
                vehiculeIdInput.value = this.dataset.id;
                matriculeInput.value = this.dataset.matricule;
                resultsContainer.style.display = 'none';
                btnAttribuer.disabled = false;
            });
            resultsContainer.appendChild(item);
        });

        resultsContainer.style.display = 'block';
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.length > 0) {
            this.dispatchEvent(new Event('input'));
        }
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });

    // Reset on modal open
    document.getElementById('modalAddVehicule').addEventListener('show.bs.modal', function() {
        searchInput.value = '';
        vehiculeIdInput.value = '';
        matriculeInput.value = '';
        resultsContainer.style.display = 'none';
        btnAttribuer.disabled = vehiculesDisponibles.length === 0;
    });

    // Validation before submit
    document.getElementById('formAddVehicule').addEventListener('submit', function(e) {
        if (!vehiculeIdInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un camion dans la liste.');
        }
    });
});
</script>
@endsection
