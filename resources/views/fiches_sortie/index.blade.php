@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des fiches de sortie</h4>
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
              <th>Usine</th>
              <th>Date chargement</th>
              <th>Date déchargement</th>
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
                <td>{{ $f->nom_pont }}</td>
                <td>{{ $f->nom_agent }}</td>
                <td>{{ $f->usine ?? '-' }}</td>
                <td>{{ $f->date_chargement ? $f->date_chargement->format('d-m-Y') : '-' }}</td>
                <td>
                  @if($f->date_dechargement)
                    {{ $f->date_dechargement->format('d-m-Y') }}
                  @else
                    <span class="text-danger">Pas encore déchargé</span>
                  @endif
                </td>
                <td>
                  @if($f->poids_pont)
                    {{ number_format((float)$f->poids_pont, 0, ',', ' ') }}
                  @else
                    <span class="text-warning">Poids pas encore renseigné</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="{{ route('fiches_sortie.show', ['fiche_id' => $f->id]) }}" class="btn btn-sm btn-outline-primary">
                      <i class="bx bx-show"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditFiche{{ $f->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteFiche{{ $f->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center">Aucune fiche de sortie</td>
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
              <label class="form-label">Usine</label>
              <select name="usine" class="form-select">
                <option value="">-- Sélectionner une usine --</option>
                @foreach($usines ?? [] as $u)
                  <option value="{{ $u['nom_usine'] ?? '' }}">{{ $u['nom_usine'] ?? '' }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de chargement <span class="text-danger">*</span></label>
              <input type="date" name="date_chargement" class="form-control" value="{{ date('Y-m-d') }}" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de déchargement</label>
              <input type="date" name="date_dechargement" class="form-control" />
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

@foreach($fiches as $f)
<!-- Modal Édition Fiche -->
<div class="modal fade" id="modalEditFiche{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Modifier la fiche de sortie</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.update', ['fiche_id' => $f->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Pont de pesage <span class="text-danger">*</span></label>
              <select name="id_pont" class="form-select" required>
                <option value="">-- Sélectionner un pont --</option>
                @foreach($ponts as $p)
                  <option value="{{ $p['id_pont'] ?? '' }}" {{ ($f->id_pont == ($p['id_pont'] ?? '')) ? 'selected' : '' }}>
                    {{ $p['nom_pont'] ?? '' }} ({{ $p['code_pont'] ?? '' }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Agent <span class="text-danger">*</span></label>
              <select name="id_agent" class="form-select" required>
                <option value="">-- Sélectionner un agent --</option>
                @foreach($agents as $a)
                  @php
                    $nomComplet = $a['nom_complet'] ?? (($a['nom_agent'] ?? '') . ' ' . ($a['prenom_agent'] ?? ''));
                    $numeroAgent = $a['numero_agent'] ?? '';
                  @endphp
                  <option value="{{ $a['id_agent'] ?? '' }}" {{ ($f->id_agent == ($a['id_agent'] ?? '')) ? 'selected' : '' }}>
                    {{ $nomComplet }} ({{ $numeroAgent }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Usine</label>
              <select name="usine" class="form-select">
                <option value="">-- Sélectionner une usine --</option>
                @foreach($usines ?? [] as $u)
                  <option value="{{ $u['nom_usine'] ?? '' }}" {{ ($f->usine == ($u['nom_usine'] ?? '')) ? 'selected' : '' }}>
                    {{ $u['nom_usine'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Chef des chargeurs</label>
              <select name="id_chef_chargeur" class="form-select">
                <option value="">-- Sélectionner un chef --</option>
                @foreach($chefChargeurs ?? [] as $chef)
                  <option value="{{ $chef->id }}" {{ ($f->id_chef_chargeur == $chef->id) ? 'selected' : '' }}>
                    {{ $chef->nom }} {{ $chef->prenoms }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de déchargement</label>
              <input type="date" name="date_dechargement" class="form-control" value="{{ $f->date_dechargement ? $f->date_dechargement->format('Y-m-d') : '' }}" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Poids (kg)</label>
              <input type="number" name="poids_pont" class="form-control" value="{{ $f->poids_pont }}" step="0.01" placeholder="Poids en kg" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Carburant (FCFA)</label>
              <input type="number" name="carburant" class="form-control" value="{{ $f->carburant }}" placeholder="Montant carburant" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Frais de route (FCFA)</label>
              <input type="number" name="frais_route" class="form-control" value="{{ $f->frais_route }}" placeholder="Frais de route" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Confirmation Suppression -->
<div class="modal fade" id="modalDeleteFiche{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-error-circle me-2"></i>Confirmation de suppression</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="bx bx-trash text-danger" style="font-size: 4rem;"></i>
        <h5 class="mt-3">Supprimer cette fiche de sortie ?</h5>
        <p class="text-muted mb-0">
          Véhicule: <strong>{{ $f->matricule_vehicule }}</strong><br>
          Pont: {{ $f->nom_pont }}<br>
          Date: {{ $f->date_chargement->format('d/m/Y') }}
        </p>
        <div class="alert alert-warning mt-3 mb-0">
          <i class="bx bx-info-circle me-1"></i>
          Cette action est irréversible.
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i>Annuler
        </button>
        <form action="{{ route('fiches_sortie.destroy', ['fiche_id' => $f->id]) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">
            <i class="bx bx-trash me-1"></i>Supprimer
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

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
