@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Soldes</h4>
      <div class="text-end">
        <div class="text-muted small">Total paiements filtrés</div>
        <div class="fw-bold text-danger">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</div>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('gestionfinanciere.index') }}" class="row g-2 align-items-end">
          <div class="col-md-4 col-lg-3">
            <label class="form-label small text-uppercase text-muted">Recherche</label>
            <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}"
              placeholder="Agent, bordereau, référence…">
          </div>
          <div class="col-md-4 col-lg-2">
            <label class="form-label small text-uppercase text-muted">Type</label>
            <select name="type" class="form-select form-select-sm">
              <option value="">Tous les paiements</option>
              <option value="agent" @selected($filters['type'] === 'agent')>Agent</option>
              <option value="particulier" @selected($filters['type'] === 'particulier')>Particulier</option>
              <option value="transporteur" @selected($filters['type'] === 'transporteur')>Transporteur</option>
              <option value="fournisseur" @selected($filters['type'] === 'fournisseur')>Fournisseur</option>
              <option value="chef_chargeur" @selected($filters['type'] === 'chef_chargeur')>Chef chargeur</option>
            </select>
          </div>
          <div class="col-md-4 col-lg-2">
            <label class="form-label small text-uppercase text-muted">Date début</label>
            <input type="date" name="date_debut" class="form-control form-control-sm" value="{{ $filters['date_debut'] }}">
          </div>
          <div class="col-md-4 col-lg-2">
            <label class="form-label small text-uppercase text-muted">Date fin</label>
            <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ $filters['date_fin'] }}">
          </div>
          <div class="col-md-8 col-lg-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bx bx-search me-1"></i>Filtrer
            </button>
            <a href="{{ route('gestionfinanciere.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bx bx-history me-1"></i>Historique des transactions</h6>
        <span class="badge bg-label-secondary">{{ $transactions->total() }} paiement(s)</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Bénéficiaire</th>
                <th>Référence</th>
                <th>Mode</th>
                <th class="text-end">Montant</th>
                <th>Note</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($transactions as $tx)
                <tr>
                  <td>{{ $tx->date?->format('d/m/Y') ?? '—' }}</td>
                  <td>
                    <span class="badge bg-label-primary">{{ $tx->type_label }}</span>
                  </td>
                  <td>
                    @if($tx->detail_url)
                      <a href="{{ $tx->detail_url }}">{{ $tx->beneficiaire }}</a>
                    @else
                      {{ $tx->beneficiaire }}
                    @endif
                  </td>
                  <td>{{ $tx->reference }}</td>
                  <td>{{ $tx->mode }}</td>
                  <td class="text-end fw-semibold text-danger">
                    {{ number_format($tx->montant, 0, ',', ' ') }} FCFA
                  </td>
                  <td>{{ $tx->note }}</td>
                  <td class="text-end">
                    @if($tx->pdf_url)
                      <a href="{{ $tx->pdf_url }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Reçu PDF">
                        <i class="bx bx-printer"></i>
                      </a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="bx bx-receipt fs-1 d-block mb-2 opacity-25"></i>
                    Aucun paiement enregistré.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($transactions->hasPages())
          <div class="mt-3">
            {{ $transactions->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
