@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    @php
      $idAgent = (int) ($agent['id_agent'] ?? 0);
      $nomComplet = $agent['nom_complet'] ?? trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? ''));
      $horsPgfQuery = $horsPgfQuery ?? (!empty($horsPgf) ? ['hors_pgf' => 1] : []);
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('gestionfinanciere.agent.show', array_merge(['id_agent' => $idAgent], $horsPgfQuery)) }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour à l'agent
        </a>
        <h4 class="mb-0">Bordereau {{ $bordereau->numero }}</h4>
        <small class="text-muted">{{ $nomComplet }} — {{ $bordereau->agent_numero ?? '' }}</small>
      </div>
      <a href="{{ route('gestionfinanciere.agent.bordereau.pdf', ['id_agent' => $idAgent, 'id' => $bordereau->id]) }}" target="_blank" class="btn btn-info">
        <i class="bx bx-printer me-1"></i>Imprimer PDF
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <small class="text-muted">Période</small>
          <p class="fw-bold mb-0">{{ $bordereau->date_debut?->format('d/m/Y') }} → {{ $bordereau->date_fin?->format('d/m/Y') }}</p>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <small class="text-muted">Fiches</small>
          <p class="fw-bold mb-0">{{ count($bordereau->fiches_data ?? []) }}</p>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <small class="text-muted">Poids total</small>
          <p class="fw-bold mb-0">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }} kg</p>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card border-danger"><div class="card-body">
          <small class="text-muted">Montant total</small>
          <p class="fw-bold mb-0 text-danger">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</p>
        </div></div>
      </div>
    </div>

    @foreach($groupesUsine as $groupe)
      <div class="card mb-4">
        <div class="card-header bg-light">
          <strong><i class="bx bx-buildings me-1"></i>{{ $groupe['usine'] }}</strong>
          <span class="float-end text-danger">{{ number_format($groupe['montant_total'], 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>N° fiche</th>
                <th>Déchargement</th>
                <th>Véhicule</th>
                <th>Produit</th>
                <th>N° ticket</th>
                <th class="text-end">Poids</th>
                <th class="text-end">Montant</th>
              </tr>
            </thead>
            <tbody>
              @foreach($groupe['lignes'] as $ligne)
                <tr>
                  <td>{{ $ligne['numero_fiche'] ?? ('#' . ($ligne['fiche_id'] ?? '')) }}</td>
                  <td>{{ !empty($ligne['date_dechargement']) ? \Carbon\Carbon::parse($ligne['date_dechargement'])->format('d/m/Y') : '-' }}</td>
                  <td>{{ $ligne['matricule_vehicule'] ?? '-' }}</td>
                  <td>{{ $ligne['nom_produit'] ?? '—' }}</td>
                  <td>{{ $ligne['numero_ticket'] ?? '—' }}</td>
                  <td class="text-end">{{ number_format((float) ($ligne['poids'] ?? 0), 0, ',', ' ') }}</td>
                  <td class="text-end text-danger">{{ number_format((int) ($ligne['montant'] ?? 0), 0, ',', ' ') }}</td>
                </tr>
              @endforeach
              <tr class="table-secondary">
                <td colspan="5" class="text-end"><strong>Sous-total {{ $groupe['usine'] }}</strong></td>
                <td class="text-end"><strong>{{ number_format($groupe['poids_total'], 0, ',', ' ') }}</strong></td>
                <td class="text-end text-danger"><strong>{{ number_format($groupe['montant_total'], 0, ',', ' ') }} FCFA</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
