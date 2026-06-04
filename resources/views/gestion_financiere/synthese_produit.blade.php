@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">
        <span class="text-muted fw-light">Gestion financière /</span> Synthèse par produit
      </h4>
      <a href="{{ route('gestionfinanciere.montant_agent', request()->only(['produit_id', 'usine', 'date_debut', 'date_fin'])) }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i>Montants agents
      </a>
    </div>

    @include('gestion_financiere._filtres_montant_agent', [
      'actionRoute' => route('gestionfinanciere.synthese_produit'),
      'filtres' => $filtres,
      'filtresActifs' => $filtresActifs,
      'produits' => $produits,
      'usines' => $usines,
    ])

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card border-start border-primary border-3">
          <div class="card-body">
            <small class="text-muted">Montant total dû</small>
            <h4 class="mb-0 text-primary">{{ number_format($totaux['montant'], 0, ',', ' ') }} FCFA</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-start border-info border-3">
          <div class="card-body">
            <small class="text-muted">Poids total</small>
            <h4 class="mb-0">{{ number_format($totaux['poids'], 0, ',', ' ') }} kg</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-start border-success border-3">
          <div class="card-body">
            <small class="text-muted">Fiches déchargées</small>
            <h4 class="mb-0">{{ number_format($totaux['fiches'], 0, ',', ' ') }}</h4>
          </div>
        </div>
      </div>
    </div>

    @forelse($synthese as $groupe)
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">
            <span class="badge bg-label-primary me-2">{{ $groupe['produit'] }}</span>
            {{ number_format($groupe['montant_total'], 0, ',', ' ') }} FCFA
            <small class="text-muted fw-normal ms-2">{{ $groupe['nb_fiches'] }} fiche(s) · {{ $groupe['nb_agents'] }} agent(s)</small>
          </h5>
          <a href="{{ route('gestionfinanciere.montant_agent', array_filter([
            'produit_id' => $groupe['produit_id'],
            'date_debut' => $filtres['date_debut'] ?? null,
            'date_fin' => $filtres['date_fin'] ?? null,
          ])) }}" class="btn btn-sm btn-outline-primary">
            Voir les agents
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>Usine</th>
                <th class="text-end">Fiches</th>
                <th class="text-end">Poids (kg)</th>
                <th class="text-end">Montant dû</th>
              </tr>
            </thead>
            <tbody>
              @foreach($groupe['usines'] as $usine)
                <tr>
                  <td><strong>{{ $usine['usine'] }}</strong></td>
                  <td class="text-end">{{ $usine['nb_fiches'] }}</td>
                  <td class="text-end">{{ number_format($usine['poids_total'], 0, ',', ' ') }}</td>
                  <td class="text-end text-danger fw-medium">{{ number_format($usine['montant_total'], 0, ',', ' ') }} FCFA</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="table-warning">
                <th>Sous-total {{ $groupe['produit'] }}</th>
                <th class="text-end">{{ $groupe['nb_fiches'] }}</th>
                <th class="text-end">{{ number_format($groupe['poids_total'], 0, ',', ' ') }}</th>
                <th class="text-end">{{ number_format($groupe['montant_total'], 0, ',', ' ') }} FCFA</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    @empty
      <div class="alert alert-info">Aucune fiche déchargée pour ces critères.</div>
    @endforelse
  </div>
</div>

@include('gestion_financiere._filtres_montant_agent_js')
@endsection
