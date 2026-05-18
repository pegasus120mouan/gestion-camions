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
        <span class="badge bg-primary fs-6">{{ $fichesSortie->total() }} fiche(s) de sortie</span>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

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
  </div>
</div>
@endsection
