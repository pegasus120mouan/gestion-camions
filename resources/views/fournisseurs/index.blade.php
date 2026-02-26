@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des fournisseurs</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddFournisseur">
        <i class="bx bx-plus"></i> Nouveau fournisseur
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Nom du fournisseur</th>
              <th>Service fourni</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fournisseurs as $fournisseur)
              <tr>
                <td><strong>{{ $fournisseur->nom }}</strong></td>
                <td>
                  <span class="badge bg-label-info">{{ $fournisseur->service->nom_service ?? '-' }}</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditFournisseur{{ $fournisseur->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteFournisseur{{ $fournisseur->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center">Aucun fournisseur enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ajouter Fournisseur -->
<div class="modal fade" id="modalAddFournisseur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nouveau fournisseur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('fournisseurs.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom du fournisseur <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" required placeholder="Nom du fournisseur" />
          </div>
          <div class="mb-3">
            <label class="form-label">Service fourni <span class="text-danger">*</span></label>
            <select name="service_id" class="form-select" required>
              <option value="">-- Sélectionner un service --</option>
              @foreach($services as $service)
                <option value="{{ $service->id }}">{{ $service->nom_service }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-check me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach($fournisseurs as $fournisseur)
<!-- Modal Modifier Fournisseur -->
<div class="modal fade" id="modalEditFournisseur{{ $fournisseur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier le fournisseur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('fournisseurs.update', $fournisseur) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom du fournisseur <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" required value="{{ $fournisseur->nom }}" />
          </div>
          <div class="mb-3">
            <label class="form-label">Service fourni <span class="text-danger">*</span></label>
            <select name="service_id" class="form-select" required>
              <option value="">-- Sélectionner un service --</option>
              @foreach($services as $service)
                <option value="{{ $service->id }}" {{ $fournisseur->service_id == $service->id ? 'selected' : '' }}>{{ $service->nom_service }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-check me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Supprimer Fournisseur -->
<div class="modal fade" id="modalDeleteFournisseur{{ $fournisseur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Supprimer le fournisseur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer le fournisseur <strong>{{ $fournisseur->nom }}</strong> ?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('fournisseurs.destroy', $fournisseur) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">
            <i class="bx bx-trash me-1"></i> Supprimer
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach
@endsection
