@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">
        <i class="bx bx-export text-primary me-2"></i>
        Sorties de stock
      </h4>
    </div>

    @if(!empty($external_error))
      <div class="alert alert-danger">{{ $external_error }}</div>
    @endif

    <!-- Résumé par pont -->
    @if(count($sortiesParPont) > 0)
      <div class="row mb-4">
        @foreach($sortiesParPont as $sortie)
          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="text-muted mb-1">{{ $sortie['nom_pont'] ?? 'Pont inconnu' }}</h6>
                    <h4 class="mb-0 text-danger">{{ number_format($sortie['total_poids'] ?? 0, 0, ',', ' ') }} kg</h4>
                    <small class="text-muted">{{ $sortie['nb_fiches'] }} fiche(s) déchargée(s)</small>
                  </div>
                  <i class="bx bx-map text-primary" style="font-size: 2.5rem; opacity: 0.5;"></i>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <!-- Liste de toutes les sorties -->
    <div class="card">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0 text-white">
          <i class="bx bx-up-arrow-circle me-2"></i>
          Toutes les sorties (Fiches déchargées)
        </h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Date déchargement</th>
              <th>Pont</th>
              <th>Véhicule</th>
              <th>Agent</th>
              <th>Usine</th>
              <th class="text-end">Poids (kg)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($fichesDechargees ?? [] as $fiche)
              <tr>
                <td>{{ $fiche->date_dechargement ? $fiche->date_dechargement->format('d-m-Y') : '-' }}</td>
                <td><strong>{{ $fiche->nom_pont ?? '-' }}</strong></td>
                <td>{{ $fiche->matricule_vehicule }}</td>
                <td>{{ $fiche->nom_agent ?? '-' }}</td>
                <td>{{ $fiche->usine ?? '-' }}</td>
                <td class="text-end">
                  <span class="badge bg-danger">{{ number_format((float)$fiche->poids_pont, 0, ',', ' ') }} kg</span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4">
                  <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                  <p class="text-muted mt-2 mb-0">Aucune sortie de stock à afficher</p>
                  <small class="text-muted">Les sorties sont enregistrées lors du déchargement des fiches de sortie</small>
                </td>
              </tr>
            @endforelse
          </tbody>
          @if(isset($fichesDechargees) && $fichesDechargees->count() > 0)
            <tfoot>
              <tr class="table-light">
                <td colspan="5" class="text-end"><strong>Total des sorties:</strong></td>
                <td class="text-end">
                  <strong class="text-danger">{{ number_format($fichesDechargees->sum('poids_pont'), 0, ',', ' ') }} kg</strong>
                </td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

  </div>
</div>
@endsection
