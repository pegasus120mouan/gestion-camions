@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('stocks_pgf.bordereaux') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux bordereaux
        </a>
        <h4 class="mb-0">
          <i class="bx bx-file text-primary me-2"></i>
          Bordereau {{ $bordereau->numero }}
        </h4>
      </div>
      <div>
        <button onclick="window.print()" class="btn btn-outline-primary">
          <i class="bx bx-printer me-1"></i>Imprimer
        </button>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations du bordereau</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <label class="form-label text-muted">Numéro</label>
            <p class="fw-bold fs-5 mb-0">{{ $bordereau->numero }}</p>
          </div>
          <div class="col-md-3">
            <label class="form-label text-muted">Date de génération</label>
            <p class="fw-bold mb-0">{{ $bordereau->date_generation ? $bordereau->date_generation->format('d-m-Y') : '-' }}</p>
          </div>
          <div class="col-md-3">
            <label class="form-label text-muted">Période</label>
            <p class="fw-bold mb-0">
              {{ $bordereau->date_debut ? $bordereau->date_debut->format('d-m-Y') : '-' }}
              <i class="bx bx-right-arrow-alt text-muted"></i>
              {{ $bordereau->date_fin ? $bordereau->date_fin->format('d-m-Y') : '-' }}
            </p>
          </div>
          <div class="col-md-3">
            <label class="form-label text-muted">Poids Total</label>
            <p class="fw-bold fs-4 text-success mb-0">{{ number_format($bordereau->poids_total, 0, ',', ' ') }} kg</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0 text-white"><i class="bx bx-map me-2"></i>Détail par pont</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Pont</th>
              <th>Code</th>
              <th class="text-end">Poids (kg)</th>
              <th class="text-end">% du total</th>
            </tr>
          </thead>
          <tbody>
            @php
              $pontsData = $bordereau->ponts_data ?? [];
              $poidsTotal = $bordereau->poids_total ?: 1;
            @endphp
            @forelse($pontsData as $pont)
              @php
                $pourcentage = ($pont['poids'] / $poidsTotal) * 100;
              @endphp
              <tr>
                <td><strong>{{ $pont['nom_pont'] ?? 'Pont #' . ($pont['id_pont'] ?? '-') }}</strong></td>
                <td><span class="badge bg-secondary">{{ $pont['code_pont'] ?? '-' }}</span></td>
                <td class="text-end fw-bold text-success">{{ number_format($pont['poids'] ?? 0, 0, ',', ' ') }}</td>
                <td class="text-end">
                  <div class="d-flex align-items-center justify-content-end">
                    <div class="progress me-2" style="width: 100px; height: 8px;">
                      <div class="progress-bar bg-primary" style="width: {{ $pourcentage }}%"></div>
                    </div>
                    <span>{{ number_format($pourcentage, 1, ',', ' ') }}%</span>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucun pont dans ce bordereau</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td colspan="2" class="text-end"><strong>Total</strong></td>
              <td class="text-end fw-bold text-success fs-5">{{ number_format($bordereau->poids_total, 0, ',', ' ') }} kg</td>
              <td class="text-end"><strong>100%</strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Tickets associés -->
    @php
      $ticketsData = $bordereau->tickets_data ?? [];
    @endphp
    @if(count($ticketsData) > 0)
    <div class="card mt-4">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0 text-white"><i class="bx bx-receipt me-2"></i>Tickets associés ({{ count($ticketsData) }})</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>N° Ticket</th>
              <th>Véhicule</th>
              <th>Pont</th>
              <th>Date chargement</th>
              <th class="text-end">Poids Usine (kg)</th>
            </tr>
          </thead>
          <tbody>
            @foreach($ticketsData as $ticket)
              <tr>
                <td><strong>{{ $ticket['numero_ticket'] ?? '-' }}</strong></td>
                <td>{{ $ticket['matricule_vehicule'] ?? '-' }}</td>
                <td><span class="badge bg-info">{{ $ticket['nom_pont'] ?? '-' }}</span></td>
                <td>{{ $ticket['date_chargement'] ?? '-' }}</td>
                <td class="text-end fw-bold text-success">{{ number_format($ticket['poids_usine'] ?? 0, 0, ',', ' ') }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td colspan="4" class="text-end"><strong>Total Poids Sortie</strong></td>
              <td class="text-end fw-bold text-danger fs-5">{{ number_format($bordereau->poids_sortie ?? 0, 0, ',', ' ') }} kg</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    @else
    <div class="card mt-4">
      <div class="card-body text-center text-muted py-4">
        <i class="bx bx-info-circle fs-1 mb-2"></i>
        <p class="mb-0">Aucun ticket associé. Cliquez sur le bouton <i class="bx bx-link"></i> dans la liste des bordereaux pour associer les tickets.</p>
      </div>
    </div>
    @endif
  </div>
</div>

<style>
@media print {
  .menu-vertical, .layout-navbar, .btn, .content-backdrop, footer {
    display: none !important;
  }
  .layout-page {
    padding: 0 !important;
    margin: 0 !important;
  }
  .content-wrapper {
    padding: 20px !important;
  }
  .card {
    border: 1px solid #ddd !important;
    box-shadow: none !important;
  }
}
</style>
@endsection
