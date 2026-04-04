@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">{{ $transporteurNom }}</h4>
        <small class="text-muted">Détail du transporteur</small>
      </div>
      <a href="{{ route('gestionfinanciere.montant_transporteur') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Retour
      </a>
    </div>

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-danger text-white">
          <div class="card-body">
            <h6 class="card-title text-white">Montant Dû</h6>
            <h3 class="mb-0">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <h6 class="card-title text-white">Montant Payé</h6>
            <h3 class="mb-0">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <h6 class="card-title text-white">Reste à Payer</h6>
            <h3 class="mb-0">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Historique des fiches de sortie ({{ $fichesSortie->count() }})</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Date Chargement</th>
              <th>Véhicule</th>
              <th>Pont</th>
              <th>Agent</th>
              <th>Usine</th>
              <th>Poids (kg)</th>
              <th>Frais Route</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fichesSortie as $fiche)
              <tr>
                <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d-m-Y') : '-' }}</td>
                <td class="fw-bold">{{ $fiche->matricule_vehicule ?? '-' }}</td>
                <td>{{ $fiche->nom_pont ?? '-' }}</td>
                <td>{{ $fiche->nom_agent ?? '-' }}</td>
                <td>{{ $fiche->usine ?? '-' }}</td>
                <td>{{ $fiche->poids_pont ? number_format($fiche->poids_pont, 0, ',', ' ') : '-' }}</td>
                <td class="text-danger fw-bold">{{ number_format($fiche->frais_route ?? 0, 0, ',', ' ') }} FCFA</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center">Aucune fiche de sortie</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
