@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
      <a href="{{ route('chef_chargeurs.index') }}" class="text-primary">
        <i class="bx bx-arrow-back me-1"></i> Retour aux chefs des chargeurs
      </a>
    </div>

    <div class="d-flex align-items-center mb-2">
      <i class="bx bx-user-check fs-3 me-2"></i>
      <h4 class="mb-0">{{ $chef->nom }} {{ $chef->prenoms }}</h4>
    </div>
    <p class="text-muted mb-4">Contact: {{ $chef->contact ?? 'Non renseigné' }}</p>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="row">
      <div class="col-md-5">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations</h5>
          </div>
          <div class="card-body">
            <p class="mb-2"><small class="text-muted">Nom</small><br><strong>{{ $chef->nom }}</strong></p>
            <p class="mb-2"><small class="text-muted">Prénoms</small><br><strong>{{ $chef->prenoms }}</strong></p>
            <p class="mb-2"><small class="text-muted">Contact</small><br><strong>{{ $chef->contact ?? '-' }}</strong></p>
            <p class="mb-2"><small class="text-muted">Nombre de chargeurs</small><br><strong>{{ $chef->chargeurs->count() }}</strong></p>
            <p class="mb-0"><small class="text-muted">Date d'ajout</small><br><strong>{{ $chef->created_at->format('d-m-Y') }}</strong></p>
          </div>
        </div>
      </div>

      <div class="col-md-7">
        <div class="card mb-4">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix unitaire par période</h5>
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalAddPrix">
              + Ajouter
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Prix (FCFA)</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($chef->prixPeriodes as $prix)
                    <tr>
                      <td><strong>{{ number_format($prix->prix_unitaire, 0, ',', ' ') }}</strong></td>
                      <td>{{ $prix->date_debut->format('d/m/Y') }}</td>
                      <td>{{ $prix->date_fin ? $prix->date_fin->format('d/m/Y') : '-' }}</td>
                      <td>
                        <div class="d-flex gap-1">
                          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPrix{{ $prix->id }}">
                            <i class="bx bx-edit"></i>
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeletePrix{{ $prix->id }}">
                            <i class="bx bx-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center text-muted py-3">Aucun prix défini</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        @if($chef->chargeurs->count() > 0)
        <div class="card mb-4">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-group me-2"></i>Chargeurs ({{ $chef->chargeurs->count() }})</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Prénoms</th>
                    <th>Contact</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($chef->chargeurs as $chargeur)
                    <tr>
                      <td><strong>{{ $chargeur->nom }}</strong></td>
                      <td>{{ $chargeur->prenoms }}</td>
                      <td>{{ $chargeur->contact ?? '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Modal Ajouter Prix -->
<div class="modal fade" id="modalAddPrix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ajouter un prix par période</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('chef_chargeurs.prix.store', $chef) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix unitaire (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix_unitaire" class="form-control" required min="0" />
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début <span class="text-danger">*</span></label>
              <input type="date" name="date_debut" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach($chef->prixPeriodes as $prix)
<!-- Modal Éditer Prix -->
<div class="modal fade" id="modalEditPrix{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier le prix</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('chef_chargeurs.prix.update', [$chef, $prix]) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix unitaire (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix_unitaire" class="form-control" value="{{ $prix->prix_unitaire }}" required min="0" />
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début <span class="text-danger">*</span></label>
              <input type="date" name="date_debut" class="form-control" value="{{ $prix->date_debut->format('Y-m-d') }}" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}" />
            </div>
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

<!-- Modal Supprimer Prix -->
<div class="modal fade" id="modalDeletePrix{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Voulez-vous vraiment supprimer le prix <strong>{{ number_format($prix->prix_unitaire, 0, ',', ' ') }} FCFA</strong> 
        (du {{ $prix->date_debut->format('d/m/Y') }}{{ $prix->date_fin ? ' au ' . $prix->date_fin->format('d/m/Y') : '' }}) ?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('chef_chargeurs.prix.destroy', [$chef, $prix]) }}" method="POST" class="d-inline">
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
