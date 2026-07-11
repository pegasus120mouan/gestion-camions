@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light"><a href="{{ route('financements.index') }}" class="text-muted">Financements</a> /</span>
      {{ $agent['nom_complet'] }}
    </h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h5 class="mb-1"><i class="bx bx-wallet me-2"></i>Détails financements</h5>
          <p class="text-muted mb-0">
            Agent : <strong>{{ $agent['nom_complet'] }}</strong>
            @if(!empty($agent['numero_agent']))
              • N° {{ $agent['numero_agent'] }}
            @endif
          </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFinancementModal">
            <i class="bx bx-plus me-1"></i>Nouveau financement
          </button>
          <a href="{{ route('financements.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bx bx-arrow-back me-1"></i>Retour
          </a>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-6 col-lg-3 mb-3">
        <div class="card h-100 border-start border-primary border-3">
          <div class="card-body text-center">
            <h4 class="text-primary mb-1">{{ number_format($stats['montant_initial'], 0, ',', ' ') }}</h4>
            <small class="text-muted">Montant initial (FCFA)</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-3">
        <div class="card h-100 border-start border-success border-3">
          <div class="card-body text-center">
            <h4 class="text-success mb-1">{{ number_format($stats['montant_rembourse'], 0, ',', ' ') }}</h4>
            <small class="text-muted">Déjà remboursé (FCFA)</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-3">
        <div class="card h-100 border-start border-warning border-3">
          <div class="card-body text-center">
            <h4 class="text-warning mb-1">{{ number_format($stats['solde_financement'], 0, ',', ' ') }}</h4>
            <small class="text-muted">Solde financement (FCFA)</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-3">
        <div class="card h-100 border-start border-info border-3">
          <div class="card-body text-center">
            <h4 class="text-info mb-1">{{ $stats['total_operations'] }}</h4>
            <small class="text-muted">Total opérations</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><i class="bx bx-filter-alt me-2"></i>Filtres de recherche</div>
      <div class="card-body">
        <form method="GET" action="{{ route('financements.show', $agent['id_agent']) }}" class="row g-3 align-items-end">
          <div class="col-md-6 col-lg-3">
            <label for="search" class="form-label">Recherche</label>
            <input type="text" name="search" id="search" class="form-control" placeholder="Numéro ou motif..." value="{{ $filters['search'] }}">
          </div>
          <div class="col-md-6 col-lg-3">
            <label for="type_filter" class="form-label">Type</label>
            <select name="type_filter" id="type_filter" class="form-select">
              <option value="">Tous</option>
              <option value="financement" @selected($filters['type_filter'] === 'financement')>Financements</option>
              <option value="remboursement" @selected($filters['type_filter'] === 'remboursement')>Remboursements</option>
            </select>
          </div>
          <div class="col-md-6 col-lg-3">
            <label for="date_debut" class="form-label">Date de début</label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $filters['date_debut'] }}">
          </div>
          <div class="col-md-6 col-lg-3">
            <label for="date_fin" class="form-label">Date de fin</label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $filters['date_fin'] }}">
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search me-1"></i>Filtrer</button>
            <a href="{{ route('financements.show', $agent['id_agent']) }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bx bx-list-ul me-2"></i>Historique des financements</span>
        <small class="text-muted">
          {{ $financements->firstItem() ?? 0 }}–{{ $financements->lastItem() ?? 0 }} / {{ $financements->total() }}
        </small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>N° financement</th>
              <th>Type</th>
              <th class="text-end">Montant</th>
              <th>Motif</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($financements as $financement)
              <tr>
                <td>{{ $financement->date_financement?->format('d/m/Y') ?? '-' }}</td>
                <td><code>{{ $financement->code_affiche }}</code></td>
                <td>
                  @if ($financement->isAdvance())
                    <span class="badge bg-label-success">Financement</span>
                  @else
                    <span class="badge bg-label-danger">Remboursement</span>
                  @endif
                </td>
                <td class="text-end {{ $financement->isAdvance() ? 'text-success' : 'text-danger' }} fw-bold">
                  {{ $financement->isAdvance() ? '+' : '' }}{{ number_format((float) $financement->montant, 0, ',', ' ') }} FCFA
                </td>
                <td>{{ $financement->motif }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Aucune opération pour cet agent.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if ($financements->hasPages())
        <div class="card-footer">{{ $financements->links() }}</div>
      @endif
    </div>

    @include('financements.partials.add-modal', [
      'agents' => $agents,
      'selectedAgentId' => $agent['id_agent'],
      'redirectTo' => route('financements.show', $agent['id_agent']),
    ])

  </div>
</div>
@endsection
