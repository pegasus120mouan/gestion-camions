@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Chargeurs</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="bx bx-plus me-1"></i> Ajouter
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Prénoms</th>
              <th>Contact</th>
              <th>Chef des chargeurs</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($chargeurs as $chargeur)
              <tr>
                <td><strong>{{ $chargeur->nom }}</strong></td>
                <td>{{ $chargeur->prenoms }}</td>
                <td>{{ $chargeur->contact ?? '-' }}</td>
                <td>
                  @if($chargeur->chefChargeur)
                    {{ $chargeur->chefChargeur->nom }} {{ $chargeur->chefChargeur->prenoms }}
                  @else
                    <span class="text-muted">Non assigné</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $chargeur->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDelete{{ $chargeur->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center">Aucun chargeur</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $chargeurs->links() }}
    </div>
  </div>
</div>

<!-- Modal Création -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ajouter un chargeur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('chargeurs.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Prénoms <span class="text-danger">*</span></label>
            <input type="text" name="prenoms" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Contact</label>
            <input type="text" name="contact" class="form-control" placeholder="Numéro de téléphone" />
          </div>
          <div class="mb-3">
            <label class="form-label">Chef des chargeurs</label>
            <select name="id_chef_chargeur" class="form-select">
              <option value="">-- Sélectionner un chef --</option>
              @foreach($chefChargeurs as $chef)
                <option value="{{ $chef->id }}">{{ $chef->nom }} {{ $chef->prenoms }}</option>
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

@foreach($chargeurs as $chargeur)
<!-- Modal Édition -->
<div class="modal fade" id="modalEdit{{ $chargeur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier le chargeur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('chargeurs.update', $chargeur) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" value="{{ $chargeur->nom }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Prénoms <span class="text-danger">*</span></label>
            <input type="text" name="prenoms" class="form-control" value="{{ $chargeur->prenoms }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Contact</label>
            <input type="text" name="contact" class="form-control" value="{{ $chargeur->contact }}" placeholder="Numéro de téléphone" />
          </div>
          <div class="mb-3">
            <label class="form-label">Chef des chargeurs</label>
            <select name="id_chef_chargeur" class="form-select">
              <option value="">-- Sélectionner un chef --</option>
              @foreach($chefChargeurs as $chef)
                <option value="{{ $chef->id }}" {{ ($chargeur->id_chef_chargeur == $chef->id) ? 'selected' : '' }}>
                  {{ $chef->nom }} {{ $chef->prenoms }}
                </option>
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

<!-- Modal Suppression -->
<div class="modal fade" id="modalDelete{{ $chargeur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Voulez-vous vraiment supprimer <strong>{{ $chargeur->nom }} {{ $chargeur->prenoms }}</strong> ?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('chargeurs.destroy', $chargeur) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach
@endsection
