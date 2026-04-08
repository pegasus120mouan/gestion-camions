@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">
          <i class="bx bx-car text-warning me-2"></i>
          Gestion des Parcs
        </h4>
        <small class="text-muted">Liste des parcs associés aux ponts</small>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParcModal">
        <i class="bx bx-plus me-1"></i> Nouveau Parc
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>
        @foreach($errors->all() as $error)
          {{ $error }}<br>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- Statistiques -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Total Parcs</h6>
                <h3 class="mb-0 text-white">{{ $parcs->count() }}</h3>
              </div>
              <i class="bx bx-car" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Parcs Actifs</h6>
                <h3 class="mb-0 text-white">{{ $parcs->where('statut', 'actif')->count() }}</h3>
              </div>
              <i class="bx bx-check-circle" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-secondary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Parcs Inactifs</h6>
                <h3 class="mb-0 text-white">{{ $parcs->where('statut', 'inactif')->count() }}</h3>
              </div>
              <i class="bx bx-x-circle" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Liste des parcs -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Liste des Parcs</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Pont Associé</th>
              <th>Statut</th>
              <th class="text-center" style="width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($parcs as $parc)
            <tr>
              <td><span class="badge bg-warning">{{ $parc->code }}</span></td>
              <td><strong>{{ $parc->nom }}</strong></td>
              <td>
                <span class="badge bg-info">{{ $parc->nom_pont ?? '-' }}</span>
                <small class="text-muted d-block">{{ $parc->code_pont ?? '' }}</small>
              </td>
              <td>
                @if($parc->isActif())
                  <span class="badge bg-success">Actif</span>
                @else
                  <span class="badge bg-secondary">Inactif</span>
                @endif
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editParcModal-{{ $parc->id }}" title="Modifier">
                  <i class="bx bx-edit-alt"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteParcModal-{{ $parc->id }}" title="Supprimer">
                  <i class="bx bx-trash"></i>
                </button>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">
                <i class="bx bx-car" style="font-size: 3rem;"></i>
                <p class="mb-0 mt-2">Aucun parc enregistré</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Modal Ajouter Parc -->
<div class="modal fade" id="addParcModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-plus-circle me-2"></i>Nouveau Parc
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('parcs.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom du parc <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" placeholder="Ex: Parc Central" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Pont associé <span class="text-danger">*</span></label>
            <select name="id_pont" class="form-select" required>
              <option value="">-- Sélectionner un pont --</option>
              @foreach($ponts as $pont)
                <option value="{{ $pont['id_pont'] ?? '' }}">{{ $pont['nom_pont'] ?? '' }} ({{ $pont['code_pont'] ?? '' }})</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals Modifier/Supprimer pour chaque parc -->
@foreach($parcs as $parc)
<!-- Modal Modifier -->
<div class="modal fade" id="editParcModal-{{ $parc->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-edit me-2"></i>Modifier le Parc
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('parcs.update', $parc->id) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" class="form-control" value="{{ $parc->code }}" disabled />
          </div>
          <div class="mb-3">
            <label class="form-label">Nom du parc <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" value="{{ $parc->nom }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Pont associé <span class="text-danger">*</span></label>
            <select name="id_pont" class="form-select" required>
              <option value="">-- Sélectionner un pont --</option>
              @foreach($ponts as $pont)
                <option value="{{ $pont['id_pont'] ?? '' }}" {{ $parc->id_pont == ($pont['id_pont'] ?? '') ? 'selected' : '' }}>
                  {{ $pont['nom_pont'] ?? '' }} ({{ $pont['code_pont'] ?? '' }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Statut <span class="text-danger">*</span></label>
            <select name="statut" class="form-select" required>
              <option value="actif" {{ $parc->statut == 'actif' ? 'selected' : '' }}>Actif</option>
              <option value="inactif" {{ $parc->statut == 'inactif' ? 'selected' : '' }}>Inactif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Supprimer -->
<div class="modal fade" id="deleteParcModal-{{ $parc->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-trash me-2"></i>Supprimer le Parc
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('parcs.destroy', $parc->id) }}">
        @csrf
        @method('DELETE')
        <div class="modal-body">
          <div class="alert alert-danger">
            <i class="bx bx-error-circle me-2"></i>
            <strong>Attention !</strong> Cette action est irréversible.
          </div>
          <p><strong>Code:</strong> {{ $parc->code }}</p>
          <p><strong>Nom:</strong> {{ $parc->nom }}</p>
          <p><strong>Pont:</strong> {{ $parc->nom_pont }}</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger">
            <i class="bx bx-trash me-1"></i> Supprimer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@endsection
