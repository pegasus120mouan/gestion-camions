@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-group text-primary me-2"></i>Groupes PGF</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddGroupe">
        <i class="bx bx-plus me-1"></i>Nouveau groupe
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Nom du groupe</th>
              <th class="text-center">Agents ordinaires</th>
              <th class="text-center">Agents pisteurs</th>
              <th class="text-center">Total agents</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($groupes as $groupe)
              <tr>
                <td>
                  <a href="{{ route('groupes.show', $groupe->id) }}" class="fw-bold text-primary">
                    {{ $groupe->nom_groupe }}
                  </a>
                </td>
                <td class="text-center">
                  <span class="badge bg-info">{{ $groupe->agents_ordinaires_count }}</span>
                </td>
                <td class="text-center">
                  <span class="badge bg-warning">{{ $groupe->agents_pisteurs_count }}</span>
                </td>
                <td class="text-center">
                  <span class="badge bg-primary">{{ $groupe->agents_count }}</span>
                </td>
                <td class="text-center">
                  <a href="{{ route('groupes.show', $groupe->id) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                    <i class="bx bx-show"></i>
                  </a>
                  <form method="POST" action="{{ route('groupes.destroy', $groupe->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce groupe ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Aucun groupe créé</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nouveau Groupe -->
<div class="modal fade" id="modalAddGroupe" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-group me-2"></i>Nouveau groupe</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('groupes.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom du groupe <span class="text-danger">*</span></label>
            <input type="text" name="nom_groupe" class="form-control" required placeholder="Ex: Groupe Nord" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Créer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
