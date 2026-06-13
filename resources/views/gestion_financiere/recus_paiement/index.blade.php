@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1"><i class="bx bx-receipt text-primary me-2"></i>Reçus de paiement</h4>
        <p class="text-muted mb-0">Tous les paiements enregistrés — génération du reçu PDF</p>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <h6 class="text-white mb-1">Paiements agents (bordereaux)</h6>
            <h4 class="mb-0">{{ number_format($totaux['agents'] ?? 0, 0, ',', ' ') }} FCFA</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-info text-white">
          <div class="card-body">
            <h6 class="text-white mb-1">Paiements particuliers</h6>
            <h4 class="mb-0">{{ number_format($totaux['particuliers'] ?? 0, 0, ',', ' ') }} FCFA</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('gestionfinanciere.recus.index') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="N° reçu, bordereau, agent, référence…">
          </div>
          <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('gestionfinanciere.recus.index') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-user-check me-2"></i>Paiements agents (via bordereau)</h5>
      </div>
      <div class="table-responsive gf-table-wrap">
        <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
          <thead>
            <tr>
              <th>N° reçu</th>
              <th>Date</th>
              <th>Agent</th>
              <th>Bordereau</th>
              <th>Mode</th>
              <th class="text-end">Montant</th>
              <th class="text-center">Reçu PDF</th>
            </tr>
          </thead>
          <tbody>
            @forelse($paiementsAgents as $p)
              <tr>
                <td><code>{{ $p->numero_recu ?? '—' }}</code></td>
                <td>{{ $p->date_paiement ? $p->date_paiement->format('d/m/Y') : '—' }}</td>
                <td>
                  <strong>{{ $p->bordereau?->agent_nom ?? 'Agent #' . $p->id_agent }}</strong>
                  @if($p->bordereau?->agent_numero)
                    <br><small class="text-muted">{{ $p->bordereau->agent_numero }}</small>
                  @endif
                </td>
                <td>
                  @if($p->bordereau)
                    <a href="{{ route('gestionfinanciere.agent.bordereau.pdf', ['id_agent' => $p->id_agent, 'id' => $p->bordereau->id]) }}" target="_blank" class="text-primary">
                      {{ $p->bordereau->numero }}
                    </a>
                  @else
                    —
                  @endif
                </td>
                <td>
                  {{ $p->mode_paiement ?? '—' }}
                  @if($p->reference)
                    <br><small class="text-muted">Réf. {{ $p->reference }}</small>
                  @endif
                </td>
                <td class="text-end text-danger fw-bold">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</td>
                <td class="text-center">
                  <a href="{{ route('gestionfinanciere.recus.pdf', $p->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Imprimer le reçu">
                    <i class="bx bx-file"></i> PDF
                  </a>
                  <a href="{{ route('gestionfinanciere.agent.show', $p->id_agent) }}" class="btn btn-sm btn-outline-primary" title="Fiche agent">
                    <i class="bx bx-show"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Aucun paiement agent enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($paiementsAgents->hasPages())
        <div class="card-footer">{{ $paiementsAgents->links() }}</div>
      @endif
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-group me-2"></i>Paiements particuliers</h5>
      </div>
      <div class="table-responsive gf-table-wrap">
        <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Agent</th>
              <th>Groupe</th>
              <th>Mode</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($paiementsParticuliers as $p)
              <tr>
                <td>{{ $p->date_paiement ? $p->date_paiement->format('d/m/Y') : '—' }}</td>
                <td>
                  <strong>{{ $p->agent?->nom_complet ?? '—' }}</strong>
                  @if($p->agent?->numero_agent)
                    <br><small class="text-muted">{{ $p->agent->numero_agent }}</small>
                  @endif
                </td>
                <td>{{ $p->agent?->groupe?->nom_groupe ?? '—' }}</td>
                <td>
                  {{ $p->mode_paiement ?? '—' }}
                  @if($p->reference)
                    <br><small class="text-muted">Réf. {{ $p->reference }}</small>
                  @endif
                </td>
                <td class="text-end text-danger fw-bold">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Aucun paiement particulier enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($paiementsParticuliers->hasPages())
        <div class="card-footer">{{ $paiementsParticuliers->links() }}</div>
      @endif
    </div>
  </div>
</div>
@endsection

@section('page-styles')
@include('gestion_financiere._table_financiere_styles')
@endsection
