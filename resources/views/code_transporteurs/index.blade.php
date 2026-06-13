@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-code-alt text-primary me-2"></i>Codes Transporteurs</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddCode">
        <i class="bx bx-plus me-1"></i>Nouveau code
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

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-search me-2"></i>Rechercher un véhicule</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('code_transporteurs.index') }}" class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Véhicule</label>
            <select name="vehicule_id" id="recherche_vehicule" class="form-select">
              <option value="" {{ !request()->filled('vehicule_id') ? 'selected' : '' }}>Choisir un véhicule…</option>
              @foreach($vehicules ?? [] as $v)
                <option value="{{ $v['vehicule_id'] }}" {{ (string) request('vehicule_id') === (string) $v['vehicule_id'] ? 'selected' : '' }}>
                  {{ $v['matricule_vehicule'] }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-search me-1"></i>Rechercher
            </button>
            @if(request()->filled('vehicule_id'))
              <a href="{{ route('code_transporteurs.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-x me-1"></i>Effacer
              </a>
            @endif
          </div>
        </form>

        @if(request()->filled('vehicule_id'))
          @if($groupeTrouve)
            <div class="alert alert-success mt-3 mb-0">
              <i class="bx bx-check-circle me-1"></i>
              Le véhicule <strong>{{ $matriculeRecherche }}</strong> appartient au groupe
              <a href="{{ route('code_transporteurs.show', $groupeTrouve->id) }}" class="alert-link fw-bold">
                {{ $groupeTrouve->nom }}
              </a>.
            </div>
          @else
            <div class="alert alert-warning mt-3 mb-0">
              <i class="bx bx-info-circle me-1"></i>
              Le véhicule <strong>{{ $matriculeRecherche ?: request('vehicule_id') }}</strong> n'est assigné à aucun groupe transporteur.
            </div>
          @endif
        @endif
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Date création</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($codes as $code)
              <tr>
                <td><strong>{{ $code->id }}</strong></td>
                <td>
                  <a href="{{ route('code_transporteurs.show', $code->id) }}" class="text-primary fw-bold text-decoration-none">
                    {{ $code->nom }}
                  </a>
                </td>
                <td>{{ $code->created_at->format('d-m-Y H:i') }}</td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditCode{{ $code->id }}">
                    <i class="bx bx-edit"></i>
                  </button>
                  <form method="POST" action="{{ route('code_transporteurs.destroy', $code->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce code transporteur ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucun code transporteur</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="card-footer">
        <strong>Total:</strong> {{ $codes->count() }} code(s) transporteur(s)
      </div>
    </div>

  </div>
</div>

<!-- Modal Ajouter -->
<div class="modal fade" id="modalAddCode" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('code_transporteurs.store') }}">
        @csrf
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Nouveau Code Transporteur</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" required placeholder="Ex: CODE-001">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals Modifier -->
@foreach($codes as $code)
<div class="modal fade" id="modalEditCode{{ $code->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('code_transporteurs.update', $code->id) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Modifier Code Transporteur</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" required value="{{ $code->nom }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach
@endsection

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
  .select2-container { width: 100% !important; }
</style>
@endsection

@section('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  $('#recherche_vehicule').select2({
    theme: 'bootstrap-5',
    placeholder: 'Choisir un véhicule…',
    allowClear: true,
    width: '100%',
    language: {
      noResults: function() { return 'Aucun véhicule trouvé'; },
      searching: function() { return 'Recherche…'; }
    }
  });
});
</script>
@endsection
