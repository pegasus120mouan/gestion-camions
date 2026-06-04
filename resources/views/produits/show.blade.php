@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
      <a href="{{ route('produits.index') }}" class="text-primary">
        <i class="bx bx-arrow-back me-1"></i> Retour aux produits
      </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="bx bx-package text-primary me-2"></i>{{ $produit->nom }}
        </h4>
        <p class="text-muted mb-0">Tare: {{ number_format($produit->tare, 3, ',', ' ') }} kg</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalAjouterUsine">
          Ajouter une usine
        </button>
        <span class="badge bg-primary fs-6">{{ $fichesSortie->total() }} fiche(s) de sortie</span>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-factory me-2"></i>Usines attribuées à {{ $produit->nom }}</h5>
      </div>
      <div class="card-body">
        @forelse($usines ?? [] as $usine)
          <span class="badge bg-label-primary me-1 mb-1" title="{{ $usine->code_usine ?? '' }}">
            {{ $usine->nom_usine }}
            @if($usine->code_usine)
              <small class="opacity-75">({{ $usine->code_usine }})</small>
            @endif
          </span>
        @empty
          <span class="text-muted">Aucune usine attribuée à ce produit</span>
        @endforelse
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0 text-white"><i class="bx bx-file me-2"></i>Fiches de sortie - {{ $produit->nom }}</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Date chargement</th>
              <th>Matricule</th>
              <th>Pont</th>
              <th>Agent</th>
              <th>Usine</th>
              <th>Poids (kg)</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @forelse($fichesSortie as $fiche)
              <tr>
                <td><strong>#{{ $fiche->id }}</strong></td>
                <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</td>
                <td>
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $fiche->vehicule_id]) }}" class="text-primary">
                    {{ $fiche->matricule_vehicule }}
                  </a>
                </td>
                <td>{{ $fiche->nom_pont ?? '-' }}</td>
                <td>{{ $fiche->nom_agent ?? '-' }}</td>
                <td>{{ $fiche->usine ?? '-' }}</td>
                <td>{{ $fiche->poids_pont ? number_format($fiche->poids_pont, 2, ',', ' ') : '-' }}</td>
                <td>
                  @if($fiche->date_dechargement)
                    <span class="badge bg-success">Déchargée</span>
                  @else
                    <span class="badge bg-warning">En attente</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4">
                  <i class="bx bx-info-circle fs-3 text-muted"></i>
                  <p class="text-muted mb-0">Aucune fiche de sortie pour ce produit</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $fichesSortie->links() }}
    </div>

    <div class="modal fade" id="modalAjouterUsine" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Ajouter une usine à {{ $produit->nom }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ route('produits.usines.store', $produit) }}">
            @csrf
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Nom de l'usine</label>
                <input type="text" name="nom_usine" class="form-control" value="{{ old('nom_usine') }}" required>
                @error('nom_usine')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="mb-3">
                <label class="form-label">Code usine</label>
                <input type="text" class="form-control bg-light" value="{{ $prochainCodeUsine ?? '—' }}" readonly />
                <small class="text-muted">Généré automatiquement à l'enregistrement.</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-primary">Valider</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    @if ($errors->has('nom_usine'))
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var el = document.getElementById('modalAjouterUsine');
          if (el && window.bootstrap) {
            new bootstrap.Modal(el).show();
          }
        });
      </script>
    @endif
  </div>
</div>
@endsection
