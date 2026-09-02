@extends('layout.main')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
      <a href="{{ route('transferts.financier.show', ['type' => $type, 'id' => $clientId]) }}" class="text-primary mb-2 d-inline-block">
        <i class="bx bx-arrow-back me-1"></i>Retour
      </a>
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
          <h4 class="mb-1"><i class="bx bx-file text-danger me-2"></i>{{ $bordereau->numero }}</h4>
          <p class="text-muted mb-0">
            {{ $bordereau->client_nom }}
            — {{ $bordereau->date_debut?->format('d/m/Y') }} → {{ $bordereau->date_fin?->format('d/m/Y') }}
          </p>
        </div>
        <a href="{{ route('transferts.financier.bordereau.pdf', ['type' => $type, 'id' => $clientId, 'bordereau' => $bordereau]) }}" target="_blank" rel="noopener" class="btn btn-info">
          <i class="bx bx-file me-1"></i>PDF
        </a>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <small class="text-muted">Généré le</small>
          <div class="fw-bold">{{ $bordereau->date_generation?->format('d/m/Y') }}</div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <small class="text-muted">Transferts</small>
          <div class="fw-bold">{{ count($bordereau->transferts_data ?? []) }}</div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <small class="text-muted">Poids</small>
          <div class="fw-bold">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }} kg</div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <small class="text-muted">Montant</small>
          <div class="fw-bold text-danger">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</div>
        </div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h5 class="mb-0">Lignes du bordereau</h5></div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Véhicule</th>
              <th>Produit</th>
              <th>Départ</th>
              <th>Destination</th>
              <th class="text-end">Poids</th>
              <th class="text-end">PU</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @foreach($bordereau->transferts_data ?? [] as $ligne)
              <tr>
                <td>{{ !empty($ligne['date_chargement']) ? \Carbon\Carbon::parse($ligne['date_chargement'])->format('d/m/Y') : '—' }}</td>
                <td><strong>{{ $ligne['matricule_vehicule'] ?? '—' }}</strong></td>
                <td>{{ $ligne['nom_produit'] ?? '—' }}</td>
                <td>{{ $ligne['lieu_depart'] ?? '—' }}</td>
                <td>{{ $ligne['lieu_destination'] ?? '—' }}</td>
                <td class="text-end">{{ number_format((float) ($ligne['poids'] ?? 0), 0, ',', ' ') }}</td>
                <td class="text-end">{{ isset($ligne['prix_unitaire']) ? number_format((float) $ligne['prix_unitaire'], 0, ',', ' ') : '—' }}</td>
                <td class="text-end text-danger fw-semibold">{{ number_format((float) ($ligne['montant'] ?? 0), 0, ',', ' ') }} FCFA</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="7" class="text-end fw-bold">Total</td>
              <td class="text-end text-danger fw-bold">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
