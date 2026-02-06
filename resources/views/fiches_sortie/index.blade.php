@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des fiches de sortie</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddFicheSortie">
        <i class="bx bx-plus me-1"></i> Ajouter une fiche de sortie
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
              <th>Pont</th>
              <th>Agent</th>
              <th>Date chargement</th>
              <th>Poids (kg)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fiches as $f)
              <tr>
                <td>
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $f->vehicule_id, 'matricule' => $f->matricule_vehicule]) }}">
                    <strong>{{ $f->matricule_vehicule }}</strong>
                  </a>
                </td>
                <td>{{ $f->nom_pont }} <small class="text-muted">({{ $f->code_pont }})</small></td>
                <td>{{ $f->nom_agent }}</td>
                <td>{{ $f->date_chargement->format('d-m-Y') }}</td>
                <td>{{ number_format((float)$f->poids_pont, 0, ',', ' ') }}</td>
                <td>
                  <a href="{{ route('vehicules.fiche_sortie', ['vehicule_id' => $f->vehicule_id, 'fiche_id' => $f->id, 'id_pont' => $f->id_pont, 'id_agent' => $f->id_agent, 'date_chargement' => $f->date_chargement->format('Y-m-d'), 'poids_pont' => $f->poids_pont]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-show"></i> Voir
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">Aucune fiche de sortie</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($fiches->count() > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Resume</h5>
          <p><strong>Total fiches:</strong> {{ $fiches->total() }}</p>
        </div>
      </div>
    @endif

    @if($fiches->hasPages())
      <div class="mt-4 d-flex justify-content-center">
        {{ $fiches->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Modal Ajouter une fiche de sortie -->
<div class="modal fade" id="modalAddFicheSortie" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bx bx-plus-circle me-2"></i>Ajouter une fiche de sortie</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.store') }}">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Véhicule <span class="text-danger">*</span></label>
              <select name="vehicule_id" id="selectVehicule" class="form-select" required>
                <option value="">-- Sélectionner un véhicule --</option>
                @foreach($vehicules as $v)
                  <option value="{{ $v['id_vehicule'] ?? '' }}" data-matricule="{{ $v['matricule_vehicule'] ?? '' }}">
                    {{ $v['matricule_vehicule'] ?? '' }}
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="matricule_vehicule" id="hiddenMatricule" value="" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Pont de pesage <span class="text-danger">*</span></label>
              <select name="id_pont" id="selectPont" class="form-select" required>
                <option value="">-- Sélectionner un pont --</option>
                @foreach($ponts as $p)
                  <option value="{{ $p['id_pont'] ?? '' }}" data-display="{{ ($p['nom_pont'] ?? '') . ' (' . ($p['code_pont'] ?? '') . ')' }}">
                    {{ $p['nom_pont'] ?? '' }} ({{ $p['code_pont'] ?? '' }})
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="pont_display" id="hiddenPontDisplay" value="" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Agent <span class="text-danger">*</span></label>
              <select name="id_agent" id="selectAgent" class="form-select" required>
                <option value="">-- Sélectionner un agent --</option>
                @foreach($agents as $a)
                  @php
                    $nomComplet = $a['nom_complet'] ?? (($a['nom_agent'] ?? '') . ' ' . ($a['prenom_agent'] ?? ''));
                    $numeroAgent = $a['numero_agent'] ?? '';
                  @endphp
                  <option value="{{ $a['id_agent'] ?? '' }}" data-display="{{ $nomComplet . ' (' . $numeroAgent . ')' }}">
                    {{ $nomComplet }} ({{ $numeroAgent }})
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="agent_display" id="hiddenAgentDisplay" value="" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de chargement <span class="text-danger">*</span></label>
              <input type="date" name="date_chargement" class="form-control" value="{{ date('Y-m-d') }}" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Poids sur le pont (kg) <span class="text-danger">*</span></label>
              <input type="number" name="poids_pont" class="form-control" step="0.01" min="0" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectVehicule = document.getElementById('selectVehicule');
  const hiddenMatricule = document.getElementById('hiddenMatricule');
  const selectPont = document.getElementById('selectPont');
  const hiddenPontDisplay = document.getElementById('hiddenPontDisplay');
  const selectAgent = document.getElementById('selectAgent');
  const hiddenAgentDisplay = document.getElementById('hiddenAgentDisplay');
  const form = document.querySelector('#modalAddFicheSortie form');

  function updateHiddenFields() {
    if (selectVehicule) {
      const selectedV = selectVehicule.options[selectVehicule.selectedIndex];
      hiddenMatricule.value = selectedV.dataset.matricule || '';
    }
    if (selectPont) {
      const selectedP = selectPont.options[selectPont.selectedIndex];
      hiddenPontDisplay.value = selectedP.dataset.display || '';
    }
    if (selectAgent) {
      const selectedA = selectAgent.options[selectAgent.selectedIndex];
      hiddenAgentDisplay.value = selectedA.dataset.display || '';
    }
  }

  if (selectVehicule) {
    selectVehicule.addEventListener('change', updateHiddenFields);
  }
  if (selectPont) {
    selectPont.addEventListener('change', updateHiddenFields);
  }
  if (selectAgent) {
    selectAgent.addEventListener('change', updateHiddenFields);
  }

  if (form) {
    form.addEventListener('submit', function(e) {
      updateHiddenFields();
    });
  }
});
</script>
@endsection
