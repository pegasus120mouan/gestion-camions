@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('groupes.index') }}" class="text-primary mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i>Retour aux groupes
        </a>
        <h4 class="mb-0"><i class="bx bx-group text-primary me-2"></i>{{ $groupe->nom_groupe }}</h4>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('groupes.tickets', $groupe->id) }}" class="btn btn-success">
          <i class="bx bx-receipt me-1"></i>Voir les tickets
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

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <!-- Résumé -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Agents ordinaires</h6>
                <h3 class="mb-0">{{ count(array_filter($agentsGroupe, fn($a) => $a['type_agent'] === 'ordinaire')) }}</h3>
              </div>
              <i class="bx bx-user" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Agents pisteurs</h6>
                <h3 class="mb-0">{{ count(array_filter($agentsGroupe, fn($a) => $a['type_agent'] === 'pisteur')) }}</h3>
              </div>
              <i class="bx bx-search-alt" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Total agents</h6>
                <h3 class="mb-0">{{ count($agentsGroupe) }}</h3>
              </div>
              <i class="bx bx-group" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Liste des agents -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Agents du groupe</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Code</th>
              <th>Nom de l'agent</th>
              <th class="text-center">Type</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($agentsGroupe as $agent)
              <tr>
                <td><code>{{ $agent['code_agent'] }}</code></td>
                <td><strong>{{ $agent['nom_agent'] }}</strong></td>
                <td class="text-center">
                  @if($agent['type_agent'] === 'ordinaire')
                    <span class="badge bg-info">Ordinaire</span>
                  @else
                    <span class="badge bg-warning">Pisteur</span>
                  @endif
                </td>
                <td class="text-center">
                  <form method="POST" action="{{ route('groupes.agent.remove', ['id' => $groupe->id, 'agent_id' => $agent['id']]) }}" class="d-inline" onsubmit="return confirm('Retirer cet agent du groupe ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer">
                      <i class="bx bx-x"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucun agent dans ce groupe</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ajouter Agent -->
<div class="modal fade" id="modalAddAgent" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-user-plus me-2"></i>Ajouter un agent</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('groupes.agent.add', $groupe->id) }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Agent <span class="text-danger">*</span></label>
            <select name="id_agent" id="selectAgent" class="form-select" required>
              <option value="">-- Sélectionner un agent --</option>
              @foreach($agentsDisponibles as $agent)
                <option value="{{ $agent['id_agent'] }}">{{ $agent['nom_complet'] ?? 'Agent' }} ({{ $agent['numero_agent'] ?? '' }})</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Type d'agent <span class="text-danger">*</span></label>
            <div class="d-flex gap-4">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type_agent" value="ordinaire" id="typeOrdinaire" checked>
                <label class="form-check-label" for="typeOrdinaire">
                  <span class="badge bg-info">Ordinaire</span>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type_agent" value="pisteur" id="typePisteur">
                <label class="form-check-label" for="typePisteur">
                  <span class="badge bg-warning">Pisteur</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Ajouter</button>
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
  $(document).ready(function() {
    $('#selectAgent').select2({
      theme: 'bootstrap-5',
      placeholder: '-- Rechercher un agent --',
      allowClear: true,
      dropdownParent: $('#modalAddAgent'),
      language: {
        noResults: function() {
          return "Aucun agent trouvé";
        },
        searching: function() {
          return "Recherche...";
        }
      }
    });
  });
</script>
@endsection
