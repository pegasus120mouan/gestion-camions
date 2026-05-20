@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-trending-down text-danger me-2"></i>Sorties (Dépenses Stocks)</h4>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <!-- Statistiques -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background: linear-gradient(135deg, #ea5455 0%, #f08182 100%);">
          <div class="card-body text-white">
            <h6 class="text-white-50 mb-1">Total sorties</h6>
            <h3 class="mb-0 text-white">{{ number_format($totalSorties ?? 0, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background: linear-gradient(135deg, #ff9f43 0%, #ffb976 100%);">
          <div class="card-body text-white">
            <h6 class="text-white-50 mb-1">Ce mois</h6>
            <h3 class="mb-0 text-white">{{ number_format($sortiesMois ?? 0, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background: linear-gradient(135deg, #00cfe8 0%, #3dd5f3 100%);">
          <div class="card-body text-white">
            <h6 class="text-white-50 mb-1">Nombre d'opérations</h6>
            <h3 class="mb-0 text-white">{{ $nbOperations ?? 0 }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ route('approvisionnements.sorties') }}" class="row g-3 align-items-end">
          <div class="col-md-3">
            <input type="text" name="pont" class="form-control" placeholder="Rechercher un pont..." value="{{ request('pont') }}">
          </div>
          <div class="col-md-3">
            <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
          </div>
          <div class="col-md-3">
            <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary me-2"><i class="bx bx-search me-1"></i>Filtrer</button>
            <a href="{{ route('approvisionnements.sorties') }}" class="btn btn-outline-secondary"><i class="bx bx-refresh"></i></a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tableau des sorties -->
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Pont</th>
              <th>Code Stock</th>
              <th>Quantité (kg)</th>
              <th>Prix unitaire</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($sorties as $sortie)
              <tr>
                <td>{{ $sortie->date_mouvement ? $sortie->date_mouvement->format('d/m/Y') : '-' }}</td>
                <td>
                  <strong>{{ $sortie->nom_pont }}</strong>
                  <small class="text-muted d-block">{{ $sortie->code_pont }}</small>
                </td>
                <td>
                  <span class="badge {{ $sortie->statut == 'ouvert' ? 'bg-success' : 'bg-secondary' }}">
                    {{ $sortie->code_stock }}
                  </span>
                </td>
                <td>{{ number_format((float)$sortie->quantite, 0, ',', ' ') }} kg</td>
                <td>{{ number_format((float)$sortie->prix_unitaire, 0, ',', ' ') }} FCFA/kg</td>
                <td class="text-end">
                  <strong class="text-danger">{{ number_format((float)$sortie->montant_total, 0, ',', ' ') }} FCFA</strong>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4">
                  <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                  <p class="text-muted mt-2 mb-0">Aucune sortie enregistrée</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        {{ $sorties->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
