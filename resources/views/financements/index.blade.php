@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="fw-bold py-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><span class="text-muted fw-light">Gestion /</span> Financements</span>
      @if(!empty($chef) || !empty($chefToken))
        <span class="badge bg-label-primary">
          Chef : {{ $chef['nom_complet'] ?? $chefToken }}
        </span>
      @endif
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

    @if(!empty($externalError))
      <div class="alert alert-warning">{{ $externalError }}</div>
    @endif

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-filter-alt me-2"></i>Filtres avancés — Financements</h5>
        <small class="text-muted">Rechercher et filtrer les financements par numéro, agent ou période.</small>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('financements.index') }}" class="row g-3 align-items-end">
          <div class="col-md-6 col-lg-3">
            <label for="search" class="form-label">Rechercher</label>
            <input type="text" name="search" id="search" class="form-control" placeholder="Nom, prénom, numéro..." value="{{ $filters['search'] ?? '' }}">
          </div>
          <div class="col-md-6 col-lg-3">
            <label for="agent_id" class="form-label">Agent</label>
            <select name="agent_id" id="agent_id" class="form-select">
              <option value="">Tous les agents</option>
              @foreach ($agents as $agentOption)
                <option value="{{ $agentOption['id_agent'] }}" @selected(($filters['agent_id'] ?? '') == $agentOption['id_agent'])>
                  {{ $agentOption['nom_complet'] }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 col-lg-3">
            <label for="date_debut" class="form-label">Date de début</label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $filters['date_debut'] ?? '' }}">
          </div>
          <div class="col-md-6 col-lg-3">
            <label for="date_fin" class="form-label">Date de fin</label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $filters['date_fin'] ?? '' }}">
          </div>
          <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i>Appliquer les filtres</button>
            <a href="{{ route('financements.index') }}" class="btn btn-outline-secondary"><i class="bx bx-reset me-1"></i>Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Résumé des financements par agent</h5>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#listeDetailleeModal">
            <i class="bx bx-list-ul me-1"></i>Liste détaillée
          </button>
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFinancementModal">
            <i class="bx bx-plus me-1"></i>Nouveau financement
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
            <tr>
              <th class="text-white text-uppercase py-3">Agent</th>
              <th class="text-white text-uppercase text-center py-3">Nombre de financements</th>
              <th class="text-white text-uppercase text-center py-3">Montant initial</th>
              <th class="text-white text-uppercase text-center py-3">Déjà remboursé</th>
              <th class="text-white text-uppercase text-center py-3">Solde financement</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($summaries as $summary)
              @php
                $badgeColor = ((int) $summary->nombre_financements) === 0 ? '#6c757d' : (((int) $summary->nombre_financements === 1) ? '#28a745' : '#007bff');
                $solde = (float) ($summary->solde_financement ?? 0);
              @endphp
              <tr>
                <td class="py-3">
                  <a href="{{ route('financements.show', $summary->id_agent) }}" class="text-primary fw-bold text-decoration-none">
                    <i class="bx bx-user me-1"></i>{{ strtoupper($summary->nom_agent ?? '-') }}
                  </a>
                </td>
                <td class="text-center py-3">
                  <span class="badge rounded-pill" style="background-color: {{ $badgeColor }}; min-width: 30px;">
                    {{ (int) ($summary->nombre_financements ?? 0) }}
                  </span>
                </td>
                <td class="text-center py-3">
                  <strong>{{ number_format((float) ($summary->montant_initial ?? 0), 0, ',', ' ') }} FCFA</strong>
                </td>
                <td class="text-center py-3">
                  <span class="text-success">{{ number_format((float) ($summary->montant_rembourse ?? 0), 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-center py-3">
                  <strong style="color: {{ $solde > 0 ? '#28a745' : '#6c757d' }};">
                    {{ number_format($solde, 0, ',', ' ') }} FCFA
                  </strong>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">Aucun agent trouvé avec les critères sélectionnés.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if ($summaries->hasPages())
        <div class="card-footer">
          {{ $summaries->links() }}
        </div>
      @else
        <div class="card-footer">
          <small class="text-muted">Affichage de {{ $summaries->firstItem() ?? 0 }} à {{ $summaries->lastItem() ?? 0 }} sur {{ $summaries->total() }} agent(s)</small>
        </div>
      @endif
    </div>

    <div class="modal fade" id="listeDetailleeModal" tabindex="-1" aria-labelledby="listeDetailleeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="listeDetailleeModalLabel">Liste détaillée des financements</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th>N° Financement</th>
                    <th>Agent</th>
                    <th>Date</th>
                    <th class="text-end">Montant</th>
                    <th>Motif</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($financements as $financement)
                    <tr>
                      <td>{{ $financement->code_financement ?? $financement->Numero_financement }}</td>
                      <td>{{ $financement->nom_agent ?? '-' }}</td>
                      <td>{{ !empty($financement->date_financement) ? \Carbon\Carbon::parse($financement->date_financement)->format('d/m/Y') : '-' }}</td>
                      <td class="text-end {{ (float) $financement->montant > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format((float) $financement->montant, 0, ',', ' ') }} FCFA
                      </td>
                      <td>{{ $financement->motif }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted py-3">Aucun financement trouvé.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>

    @include('financements.partials.add-modal', [
      'agents' => $agents,
      'redirectTo' => route('financements.index'),
    ])

  </div>
</div>
@endsection

@section('page-scripts')
  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('addFinancementModal');
        if (modalEl) {
          var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          modal.show();
        }
      });
    </script>
  @endif
@endsection
