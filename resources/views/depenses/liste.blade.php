@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des depenses</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelleDepense">
        <i class="bx bx-plus me-1"></i>Nouvelle dépense
      </button>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('depenses.liste') }}" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Véhicule</label>
            <select name="vehicule" class="form-select">
              <option value="">Tous les véhicules</option>
              @foreach($vehicules ?? [] as $v)
                <option value="{{ $v['matricule_vehicule'] }}" {{ request('vehicule') == $v['matricule_vehicule'] ? 'selected' : '' }}>
                  {{ $v['matricule_vehicule'] }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Service</label>
            <select name="service" class="form-select">
              <option value="">Tous les services</option>
              @foreach($services ?? [] as $service)
                <option value="{{ $service->nom_service }}" {{ request('service') == $service->nom_service ? 'selected' : '' }}>
                  {{ $service->nom_service }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Fournisseur</label>
            <select name="fournisseur" class="form-select">
              <option value="">Tous les fournisseurs</option>
              @foreach($fournisseurs ?? [] as $fournisseur)
                <option value="{{ $fournisseur->nom }}" {{ request('fournisseur') == $fournisseur->nom ? 'selected' : '' }}>
                  {{ $fournisseur->nom }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
          </div>
          <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bx bx-search"></i>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Vehicule</th>
              <th>Service</th>
              <th>Fournisseur</th>
              <th>Montant</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($depenses as $d)
              <tr>
                <td>
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $d->vehicule_id, 'matricule' => $d->matricule_vehicule]) }}">
                    {{ $d->matricule_vehicule }}
                  </a>
                </td>
                <td>
                  <span class="badge bg-primary">{{ $d->type_depense ?? '-' }}</span>
                </td>
                <td>
                  @if($d->description)
                    <a href="{{ route('gestionfinanciere.fournisseur.show', ['nom' => $d->description]) }}" class="text-primary">{{ $d->description }}</a>
                  @else
                    -
                  @endif
                </td>
                <td>{{ number_format((float)($d->montant ?? 0), 0, ',', ' ') }} FCFA</td>
                <td>
                  @if($d->date_depense)
                    {{ $d->date_depense->format('d-m-Y') }}
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center">Aucune depense</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($depenses->count() > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Resume</h5>
          @php
            $totalDepenses = $depenses->sum('montant');
          @endphp
          <p><strong>Total depenses (page):</strong> <span class="text-danger">{{ number_format($totalDepenses, 0, ',', ' ') }} FCFA</span></p>
        </div>
      </div>
    @endif

    @if(method_exists($depenses, 'hasPages') && $depenses->hasPages())
      <div class="mt-4 d-flex justify-content-center">
        {{ $depenses->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Modal Nouvelle Dépense -->
<div class="modal fade" id="modalNouvelleDepense" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-plus me-2"></i>Nouvelle dépense</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('depenses.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Véhicule</label>
            <select name="vehicule_id" class="form-select" required id="selectVehicule">
              <option value="">-- Sélectionner un véhicule --</option>
              @foreach($vehicules ?? [] as $v)
                <option value="{{ $v['vehicules_id'] }}" data-matricule="{{ $v['matricule_vehicule'] }}">
                  {{ $v['matricule_vehicule'] }} ({{ $v['type_vehicule'] ?? '-' }})
                </option>
              @endforeach
            </select>
            <input type="hidden" name="matricule_vehicule" id="matriculeVehicule">
          </div>
          <div class="mb-3">
            <label class="form-label">Service</label>
            <select name="type_depense" id="service_select_liste" class="form-select" required>
              <option value="">-- Sélectionner un service --</option>
              @foreach($services ?? [] as $service)
                <option value="{{ $service->nom_service }}" data-service-id="{{ $service->id }}">{{ $service->nom_service }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3" id="fournisseur_container_liste" style="display: none;">
            <label class="form-label">Fournisseur</label>
            <select name="description" id="fournisseur_select_liste" class="form-select">
              <option value="">-- Sélectionner un fournisseur --</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="number" name="montant" class="form-control" required min="0" step="1">
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date_depense" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
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

  // Gestion des fournisseurs par service
  var fournisseursData = {
    @foreach($services ?? [] as $service)
      "{{ $service->id }}": [
        @foreach($fournisseurs ?? [] as $fournisseur)
          @if($fournisseur->service_id == $service->id)
            { id: {{ $fournisseur->id }}, nom: "{{ $fournisseur->nom }}" },
          @endif
        @endforeach
      ],
    @endforeach
  };

  var serviceSelect = document.getElementById('service_select_liste');
  var fournisseurContainer = document.getElementById('fournisseur_container_liste');
  var fournisseurSelect = document.getElementById('fournisseur_select_liste');

  if (serviceSelect && fournisseurSelect) {
    serviceSelect.addEventListener('change', function() {
      var selectedOption = this.options[this.selectedIndex];
      var serviceId = selectedOption.getAttribute('data-service-id');
      
      // Vider le select des fournisseurs
      fournisseurSelect.innerHTML = '<option value="">-- Sélectionner un fournisseur --</option>';
      
      if (serviceId && fournisseursData[serviceId] && fournisseursData[serviceId].length > 0) {
        fournisseursData[serviceId].forEach(function(f) {
          var option = document.createElement('option');
          option.value = f.nom;
          option.textContent = f.nom;
          fournisseurSelect.appendChild(option);
        });
        fournisseurContainer.style.display = 'block';
      } else {
        fournisseurContainer.style.display = 'none';
      }
    });
  }
});
</script>
@endsection
