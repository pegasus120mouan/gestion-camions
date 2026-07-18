@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('bilan-vehicule.index') }}" class="text-primary mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux catégories
        </a>
        <h4 class="mb-0"><i class="bx bx-car me-2"></i>Bilan - {{ $vehicule->matricule_vehicule }}</h4>
        <small class="text-muted">Catégorie : {{ $categorieLabel }}</small>
      </div>
    </div>

    <!-- Statistiques du véhicule -->
    <div class="row mb-4">
      <div class="col-md-2">
        <div class="card bg-info text-white">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <i class="bx bx-file fs-1 me-3"></i>
              <div>
                <h6 class="text-white mb-0">Fiches</h6>
                <h3 class="text-white mb-0">{{ $fiches->count() }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card bg-secondary text-white">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <i class="bx bx-package fs-1 me-3"></i>
              <div>
                <h6 class="text-white mb-0">Poids</h6>
                <h3 class="text-white mb-0">{{ number_format($totalPoids, 0, ',', ' ') }} kg</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card bg-success text-white">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <i class="bx bx-dollar-circle fs-1 me-3"></i>
              <div>
                <h6 class="text-white mb-0">Montant</h6>
                <h3 class="text-white mb-0">{{ number_format($totalMontantCamion, 0, ',', ' ') }} F</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card bg-danger text-white">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <i class="bx bx-money fs-1 me-3"></i>
              <div>
                <h6 class="text-white mb-0">Dépenses</h6>
                <h3 class="text-white mb-0">{{ number_format($totalDepenses, 0, ',', ' ') }} F</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card {{ $marge >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <i class="bx {{ $marge >= 0 ? 'bx-trending-up' : 'bx-trending-down' }} fs-1 me-3"></i>
              <div>
                <h6 class="text-white mb-0">Marge ({{ $marge >= 0 ? 'Gain' : 'Perte' }})</h6>
                <h3 class="text-white mb-0">{{ number_format($marge, 0, ',', ' ') }} F</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Liste des fiches de sortie -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Historique des fiches de sortie</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Pont</th>
              <th>Produit</th>
              <th class="text-end">Poids</th>
              <th class="text-end">Montant</th>
              <th class="text-end">Dépenses</th>
              <th class="text-end">Marge</th>
            </tr>
          </thead>
          <tbody>
            @forelse($fiches as $fiche)
            @php
              $depensesFiche = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0);
              $margeFiche = ($fiche->montant_camion ?? 0) - $depensesFiche;
            @endphp
            <tr>
              <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</td>
              <td>
                <span class="badge bg-primary">{{ $fiche->nom_pont ?? '-' }}</span>
              </td>
              <td>{{ $fiche->nom_produit ?? '-' }}</td>
              <td class="text-end">
                @if($fiche->poids_pont)
                <strong>{{ number_format($fiche->poids_pont, 0, ',', ' ') }} kg</strong>
                @else
                <span class="text-muted">-</span>
                @endif
              </td>
              <td class="text-end">
                @if($fiche->montant_camion)
                <strong class="text-success">{{ number_format($fiche->montant_camion, 0, ',', ' ') }} F</strong>
                @else
                <span class="text-muted">-</span>
                @endif
              </td>
              <td class="text-end">
                <strong class="text-danger">{{ number_format($depensesFiche, 0, ',', ' ') }} F</strong>
              </td>
              <td class="text-end">
                <strong class="{{ $margeFiche >= 0 ? 'text-success' : 'text-danger' }}">
                  {{ number_format($margeFiche, 0, ',', ' ') }} F
                </strong>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="text-center py-4">
                <i class="bx bx-file text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">Aucune fiche de sortie pour ce véhicule</p>
              </td>
            </tr>
            @endforelse
          </tbody>
          @if($fiches->count() > 0)
          <tfoot class="table-secondary">
            <tr>
              <td colspan="3"><strong>TOTAL</strong></td>
              <td class="text-end"><strong>{{ number_format($totalPoids, 0, ',', ' ') }} kg</strong></td>
              <td class="text-end"><strong class="text-success">{{ number_format($totalMontantCamion, 0, ',', ' ') }} F</strong></td>
              <td class="text-end"><strong class="text-danger">{{ number_format($totalDepenses, 0, ',', ' ') }} F</strong></td>
              <td class="text-end"><strong class="{{ $marge >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($marge, 0, ',', ' ') }} F</strong></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>

  </div>
</div>
@endsection
