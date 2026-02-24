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

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Vehicule</th>
              <th>Type</th>
              <th>Description</th>
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
                  @php
                    $type = $d->type_depense ?? '';
                  @endphp
                  @if($type === 'carburant')
                    <span class="badge bg-warning">Carburant</span>
                  @elseif($type === 'pieces')
                    <span class="badge bg-info">Pieces</span>
                  @elseif($type === 'entretien')
                    <span class="badge bg-primary">Entretien</span>
                  @elseif($type === 'reparation')
                    <span class="badge bg-danger">Reparation</span>
                  @else
                    <span class="badge bg-secondary">{{ $type }}</span>
                  @endif
                </td>
                <td>{{ $d->description ?? '-' }}</td>
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
            <label class="form-label">Type de dépense</label>
            <select name="type_depense" class="form-select" required>
              <option value="">-- Sélectionner --</option>
              <option value="carburant">Carburant</option>
              <option value="pieces">Pièces</option>
              <option value="entretien">Entretien</option>
              <option value="reparation">Réparation</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
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
});
</script>
@endsection
