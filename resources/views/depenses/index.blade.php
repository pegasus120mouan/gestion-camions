@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Depenses du vehicule</h4>
        @if(isset($vehicule) && is_array($vehicule))
          <p class="text-muted mb-0">{{ $vehicule['matricule_vehicule'] ?? '' }}</p>
        @endif
      </div>
      <div>
        <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#modalFicheSortie">
          <i class="bx bx-file"></i> Fiche de sortie
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelleDepense">
          Nouvelle depense
        </button>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
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
                <td colspan="4" class="text-center">Aucune depense</td>
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
        {{ $depenses->appends(['matricule' => $vehicule['matricule_vehicule'] ?? ''])->links() }}
      </div>
    @endif

    <div class="mt-3">
      <a href="{{ route('camions.index') }}" class="btn btn-outline-secondary">Retour aux camions</a>
    </div>
  </div>
</div>

<!-- Modal Fiche de Sortie -->
<div class="modal fade" id="modalFicheSortie" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Fiche de sortie - {{ $vehicule['matricule_vehicule'] ?? '' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('vehicules.fiche_sortie.store', ['vehicule_id' => $vehicule_id]) }}" id="formFicheSortie">
          @csrf
          <input type="hidden" name="id_pont" id="id_pont_hidden" />
          <input type="hidden" name="id_agent" id="id_agent_hidden" />
          <input type="hidden" name="pont_display" id="pont_display_hidden" />
          <input type="hidden" name="agent_display" id="agent_display_hidden" />
          <input type="hidden" name="matricule_vehicule" value="{{ $vehicule['matricule_vehicule'] ?? request('matricule', '') }}" />

          <div class="mb-3">
            <label class="form-label">Date de chargement <span class="text-danger">*</span></label>
            <input type="date" name="date_chargement" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>

          <div class="mb-3">
            <label class="form-label">Pont de pesage <span class="text-danger">*</span></label>
            <input type="text" id="pont_input" class="form-control" placeholder="Tapez pour rechercher un pont..." list="ponts_list" autocomplete="off" required />
            <datalist id="ponts_list">
              @foreach($ponts ?? [] as $pont)
                <option data-id="{{ $pont['id_pont'] }}" value="{{ $pont['nom_pont'] }} ({{ $pont['code_pont'] }})">
              @endforeach
            </datalist>
          </div>

          <div class="mb-3">
            <label class="form-label">Agent <span class="text-danger">*</span></label>
            <input type="text" id="agent_input" class="form-control" placeholder="Tapez pour rechercher un agent..." list="agents_list" autocomplete="off" required />
            <datalist id="agents_list">
              @foreach($agents ?? [] as $agent)
                <option data-id="{{ $agent['id_agent'] }}" value="{{ $agent['nom_complet'] }} ({{ $agent['numero_agent'] }})">
              @endforeach
            </datalist>
          </div>

          <div class="mb-3">
            <label class="form-label">Usine</label>
            <select name="usine" class="form-select">
              <option value="">-- Sélectionner une usine --</option>
              @foreach($usines ?? [] as $u)
                <option value="{{ $u['nom_usine'] ?? '' }}">{{ $u['nom_usine'] ?? '' }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Chef des chargeurs</label>
            <select name="id_chef_chargeur" class="form-select">
              <option value="">-- Sélectionner un chef des chargeurs --</option>
              @foreach($chefChargeurs ?? [] as $chef)
                <option value="{{ $chef->id }}">{{ $chef->nom }} {{ $chef->prenoms }}</option>
              @endforeach
            </select>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Carburant (FCFA)</label>
              <input type="number" name="carburant" class="form-control" min="0" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Frais de route (FCFA)</label>
              <input type="number" name="frais_route" class="form-control" min="0" />
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save"></i> Enregistrer la fiche
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mapping ponts
  var pontsMap = {
    @foreach($ponts ?? [] as $pont)
      "{{ $pont['nom_pont'] }} ({{ $pont['code_pont'] }})": {{ $pont['id_pont'] }},
    @endforeach
  };

  // Mapping agents
  var agentsMap = {
    @foreach($agents ?? [] as $agent)
      "{{ $agent['nom_complet'] }} ({{ $agent['numero_agent'] }})": {{ $agent['id_agent'] }},
    @endforeach
  };

  var pontInput = document.getElementById('pont_input');
  var agentInput = document.getElementById('agent_input');
  var idPontHidden = document.getElementById('id_pont_hidden');
  var idAgentHidden = document.getElementById('id_agent_hidden');
  var pontDisplayHidden = document.getElementById('pont_display_hidden');
  var agentDisplayHidden = document.getElementById('agent_display_hidden');

  if (pontInput) {
    pontInput.addEventListener('change', function() {
      var val = this.value;
      idPontHidden.value = pontsMap[val] || '';
      pontDisplayHidden.value = val;
    });
    pontInput.addEventListener('input', function() {
      var val = this.value;
      idPontHidden.value = pontsMap[val] || '';
      pontDisplayHidden.value = val;
    });
  }

  if (agentInput) {
    agentInput.addEventListener('change', function() {
      var val = this.value;
      idAgentHidden.value = agentsMap[val] || '';
      agentDisplayHidden.value = val;
    });
    agentInput.addEventListener('input', function() {
      var val = this.value;
      idAgentHidden.value = agentsMap[val] || '';
      agentDisplayHidden.value = val;
    });
  }

  // Validation avant soumission
  var formFicheSortie = document.getElementById('formFicheSortie');
  if (formFicheSortie) {
    formFicheSortie.addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (!idPontHidden.value) {
        alert('Veuillez selectionner un pont valide dans la liste.');
        pontInput.focus();
        return false;
      }
      if (!idAgentHidden.value) {
        alert('Veuillez selectionner un agent valide dans la liste.');
        agentInput.focus();
        return false;
      }

      var formData = new FormData(formFicheSortie);
      var submitBtn = formFicheSortie.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...';

      fetch(formFicheSortie.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(data) {
        if (data.success) {
          // Fermer le modal de création
          var modalFicheSortie = bootstrap.Modal.getInstance(document.getElementById('modalFicheSortie'));
          if (modalFicheSortie) modalFicheSortie.hide();
          
          // Afficher le modal de succès
          var successModal = new bootstrap.Modal(document.getElementById('modalSuccessFiche'));
          successModal.show();
          
          // Rediriger après 2 secondes
          setTimeout(function() {
            window.location.href = '{{ route("fiches_sortie.index") }}';
          }, 2000);
        } else {
          alert(data.message || 'Une erreur est survenue.');
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="bx bx-save"></i> Enregistrer la fiche';
        }
      })
      .catch(function(error) {
        alert('Une erreur est survenue lors de l\'enregistrement.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bx bx-save"></i> Enregistrer la fiche';
      });
    });
  }
});
</script>

<!-- Modal Succès Fiche -->
<div class="modal fade" id="modalSuccessFiche" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-body py-5">
        <div class="mb-4">
          <i class="bx bx-check-circle text-success" style="font-size: 5rem;"></i>
        </div>
        <h4 class="text-success mb-3">Fiche créée avec succès !</h4>
        <p class="text-muted mb-0">Vous allez être redirigé vers la liste des fiches de sortie...</p>
        <div class="mt-3">
          <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalNouvelleDepense" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nouvelle depense</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('vehicules.depenses.store', ['vehicule_id' => $vehicule['vehicules_id'] ?? 0]) }}">
          @csrf
          <input type="hidden" name="matricule_vehicule" value="{{ $vehicule['matricule_vehicule'] ?? '' }}" />

          <div class="mb-3">
            <label class="form-label">Type de depense</label>
            <select name="type_depense" class="form-select" required>
              <option value="">-- Choisir --</option>
              <option value="carburant">Carburant</option>
              <option value="pieces">Achat de pieces</option>
              <option value="entretien">Entretien</option>
              <option value="reparation">Reparation</option>
              <option value="autre">Autre</option>
            </select>
            @error('type_depense')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Details de la depense..."></textarea>
            @error('description')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="montant_input" class="form-control" required placeholder="200 000" />
            @error('montant')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date_depense" class="form-control" value="{{ date('Y-m-d') }}" required />
            @error('date_depense')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@if ($errors->any())
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var el = document.getElementById('modalNouvelleDepense');
      if (el && window.bootstrap) {
        new bootstrap.Modal(el).show();
      }
    });
  </script>
@endif

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var montantInput = document.getElementById('montant_input');
    if (montantInput) {
      montantInput.addEventListener('input', function (e) {
        var value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
        if (value) {
          e.target.value = parseInt(value, 10).toLocaleString('fr-FR').replace(/,/g, ' ');
        }
      });

      montantInput.form.addEventListener('submit', function () {
        montantInput.value = montantInput.value.replace(/\s/g, '');
      });
    }
  });
</script>
@endsection
