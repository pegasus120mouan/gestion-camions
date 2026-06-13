@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('particuliers.index') }}" class="text-primary mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i>Retour aux groupes particuliers
        </a>
        <h4 class="mb-0"><i class="bx bx-group text-primary me-2"></i>{{ $groupe->nom_groupe }}</h4>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('particuliers.agents.index', ['particulier_groupe_id' => $groupe->id]) }}" class="btn btn-outline-primary">
          <i class="bx bx-list-ul me-1"></i>Tous les agents
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddAgent">
          <i class="bx bx-plus me-1"></i>Ajouter un agent
        </button>
      </div>
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

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Agents API (groupe)</h6>
                <h3 class="mb-0">{{ count($agentsGroupeApi ?? []) }}</h3>
                <small class="text-white-50">Enregistrés localement : {{ $groupe->agents->count() }}</small>
              </div>
              <i class="bx bx-group" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if(empty($agentsGroupeApi) && ($agentsApiTotal ?? 0) === 0)
      <div class="alert alert-warning">
        Impossible de charger les agents depuis l’API. Vérifiez votre connexion ou reconnectez-vous à l’application.
      </div>
    @endif

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Agents du groupe (API)</h5>
        <small class="text-muted">{{ count($agentsGroupeApi ?? []) }} agent(s)</small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>N° agent</th>
              <th>Nom complet</th>
              <th>Contact</th>
              <th>Chef d'équipe</th>
              <th>Statut local</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($agentsGroupeApi ?? [] as $a)
              @php
                $idAgent = (int) ($a['id_agent'] ?? 0);
                $local = ($agentsLocauxByIdAgent ?? collect())->get($idAgent);
              @endphp
              <tr>
                <td><code>{{ $a['numero_agent'] ?? '-' }}</code></td>
                <td><strong>{{ $a['nom_complet'] ?? '-' }}</strong></td>
                <td>{{ $a['contact'] ?? '-' }}</td>
                <td>{{ $a['chef_equipe']['nom_complet'] ?? ($a['chef_equipe']['nom'] ?? '-') }}</td>
                <td>
                  @if($local)
                    <span class="badge bg-label-success">Enregistré</span>
                  @else
                    <span class="badge bg-label-secondary">API uniquement</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($local)
                    <form method="POST" action="{{ route('particuliers.agents.destroy', $local) }}" class="d-inline" onsubmit="return confirm('Retirer cet agent du groupe ?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer du groupe">
                        <i class="bx bx-trash"></i>
                      </button>
                    </form>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Aucun agent trouvé dans l’API pour ce groupe</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAddAgent" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-user-plus me-2"></i>Ajouter un agent</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('particuliers.agents.store') }}" id="formAddAgentApi">
        @csrf
        <input type="hidden" name="particulier_groupe_id" value="{{ $groupe->id }}" />
        <input type="hidden" name="id_agent" id="hidden_id_agent" value="" />
        <input type="hidden" name="numero_api" id="hidden_numero_api" value="" />
        <input type="hidden" name="nom_api" id="hidden_nom_api" value="" />
        <input type="hidden" name="prenoms_api" id="hidden_prenoms_api" value="" />
        <input type="hidden" name="contact_api" id="hidden_contact_api" value="" />
        <div class="modal-body">
          @if(count($agentsDisponibles ?? []) > 0)
            <div class="mb-3">
              <label class="form-label">Sélectionner un agent <span class="text-danger">*</span></label>
              <select class="form-select" id="selectAgentApi" required>
                <option value="">-- Choisir un agent --</option>
                @foreach($agentsDisponibles as $a)
                  <option value="{{ $a['id_agent'] }}"
                    data-numero="{{ $a['numero_agent'] ?? '' }}"
                    data-nom="{{ $a['nom_complet'] ?? '' }}"
                    data-prenoms=""
                    data-contact="{{ $a['contact'] ?? '' }}">
                    {{ $a['numero_agent'] ?? '' }} – {{ $a['nom_complet'] ?? '' }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Tapez pour rechercher par numéro ou nom</small>
            </div>
          @else
            <p class="text-muted mb-0">Tous les agents de ce groupe sont déjà enregistrés dans un groupe particulier.</p>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" @if(empty($agentsDisponibles)) disabled @endif>
            <i class="bx bx-check me-1"></i>Ajouter
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
  }
  .select2-container {
    width: 100% !important;
  }
</style>
@endsection

@section('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function() {
  function syncAgentHiddenFields() {
    var select = document.getElementById('selectAgentApi');
    if (!select) return;
    var opt = select.options[select.selectedIndex];
    document.getElementById('hidden_id_agent').value    = opt ? opt.value : '';
    document.getElementById('hidden_numero_api').value  = opt ? (opt.dataset.numero || '') : '';
    document.getElementById('hidden_nom_api').value     = opt ? (opt.dataset.nom || '') : '';
    document.getElementById('hidden_prenoms_api').value = opt ? (opt.dataset.prenoms || '') : '';
    document.getElementById('hidden_contact_api').value = opt ? (opt.dataset.contact || '') : '';
  }

  function resetAgentForm() {
    var select = document.getElementById('selectAgentApi');
    if (!select) return;
    if ($(select).hasClass('select2-hidden-accessible')) {
      $(select).val('').trigger('change');
    } else {
      select.value = '';
    }
    syncAgentHiddenFields();
  }

  function initAgentSelect2() {
    var $select = $('#selectAgentApi');
    if (!$select.length) return;

    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      theme: 'bootstrap-5',
      placeholder: '-- Rechercher un agent --',
      allowClear: true,
      dropdownParent: $('#modalAddAgent'),
      width: '100%',
      language: {
        noResults: function() { return 'Aucun agent trouvé'; },
        searching: function() { return 'Recherche...'; }
      }
    }).on('change', syncAgentHiddenFields);
  }

  $(document).ready(function() {
    $('#modalAddAgent').on('shown.bs.modal', initAgentSelect2);
    $('#modalAddAgent').on('hidden.bs.modal', function() {
      var $select = $('#selectAgentApi');
      if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
      }
      resetAgentForm();
    });
  });
})();
</script>
@endsection
