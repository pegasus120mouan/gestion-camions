@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-package text-primary me-2"></i>Gestion des Stocks PGF</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddStock">
        <i class="bx bx-plus me-1"></i>Nouveau Stock
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Code</th>
              <th>Date début</th>
              <th>Date fin</th>
              <th>Statut</th>
              <th class="text-end">Total Entrées (kg)</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stocks as $stock)
              <tr>
                <td>
                  <a href="{{ route('stocks_pgf.show', $stock->id) }}" class="fw-bold text-primary">
                    {{ $stock->code }}
                  </a>
                </td>
                <td>{{ $stock->date_debut ? $stock->date_debut->format('d-m-Y') : '-' }}</td>
                <td>{{ $stock->date_fin ? $stock->date_fin->format('d-m-Y') : '-' }}</td>
                <td>
                  @if($stock->statut === 'actif')
                    <span class="badge bg-success">Actif</span>
                  @else
                    <span class="badge bg-secondary">Clôturé</span>
                  @endif
                </td>
                <td class="text-end">{{ number_format($stock->entrees_sum_quantite ?? 0, 0, ',', ' ') }}</td>
                <td class="text-center">
                  <a href="{{ route('stocks_pgf.show', $stock->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-show"></i>
                  </a>
                  @if($stock->statut === 'actif')
                    <form method="POST" action="{{ route('stocks_pgf.cloturer', $stock->id) }}" class="d-inline" onsubmit="return confirm('Clôturer ce stock ?')">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn btn-sm btn-outline-warning" title="Clôturer">
                        <i class="bx bx-lock"></i>
                      </button>
                    </form>
                  @endif
                  <form method="POST" action="{{ route('stocks_pgf.destroy', $stock->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce stock ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Aucun stock enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nouveau Stock -->
<div class="modal fade" id="modalAddStock" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-plus me-2"></i>Nouveau Stock</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('stocks_pgf.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" class="form-control" value="Auto-généré" disabled />
            <small class="text-muted">Le code sera généré automatiquement</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Date début <span class="text-danger">*</span></label>
            <input type="date" name="date_debut" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" />
            <small class="text-muted">Optionnel - sera définie à la clôture si non renseignée</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Créer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
