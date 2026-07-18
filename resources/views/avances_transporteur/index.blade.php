@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold mb-4">
      <span class="text-muted fw-light">Gestion /</span> Historiques des avances transporteurs
    </h4>

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('avances_transporteur.index') }}" class="row g-3 align-items-end">
          <div class="col-md-8">
            <label for="search" class="form-label">Rechercher un transporteur</label>
            <input type="text" name="search" id="search" class="form-control"
              placeholder="Code, nom ou prénoms…" value="{{ $search }}">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-search me-1"></i>Rechercher
            </button>
            <a href="{{ route('avances_transporteur.index') }}" class="btn btn-outline-secondary">
              <i class="bx bx-reset me-1"></i>Réinitialiser
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Transporteurs</h5>
        <small class="text-muted">Sélectionnez un transporteur pour consulter son historique d’avances.</small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
            <tr>
              <th class="text-white text-uppercase py-3">Transporteur</th>
              <th class="text-white text-uppercase text-center py-3">Nombre d’avances</th>
              <th class="text-white text-uppercase text-center py-3">Montant total</th>
              <th class="text-white text-uppercase text-center py-3">Solde restant</th>
              <th class="text-white text-uppercase text-center py-3">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($summaries as $summary)
              <tr>
                <td class="py-3 fw-semibold">
                  <i class="bx bx-bus me-1"></i>
                  {{ $summary->code }} — {{ $summary->nom }} {{ $summary->prenoms }}
                </td>
                <td class="text-center py-3">
                  <span class="badge rounded-pill bg-{{ (int) $summary->nombre_avances > 0 ? 'primary' : 'secondary' }}">
                    {{ (int) $summary->nombre_avances }}
                  </span>
                </td>
                <td class="text-center py-3">
                  <strong class="text-success">
                    {{ number_format((float) $summary->montant_total, 0, ',', ' ') }} FCFA
                  </strong>
                </td>
                <td class="text-center py-3">
                  <strong class="{{ (float) $summary->solde_restant > 0 ? 'text-primary' : 'text-muted' }}">
                    {{ number_format((float) $summary->solde_restant, 0, ',', ' ') }} FCFA
                  </strong>
                </td>
                <td class="text-center py-3">
                  <a href="{{ route('avances_transporteur.show', $summary->id) }}" class="btn btn-sm btn-outline-success">
                    <i class="bx bx-history me-1"></i>Voir l’historique
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">Aucun transporteur trouvé.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($summaries->hasPages())
        <div class="card-footer">{{ $summaries->links() }}</div>
      @endif
    </div>
  </div>
</div>
@endsection
