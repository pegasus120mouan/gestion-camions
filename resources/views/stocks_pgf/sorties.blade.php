@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-up-arrow-circle text-primary me-2"></i>Sorties de Stock PGF</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGenererBordereau">
        <i class="bx bx-plus me-1"></i>Générer un bordereau
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Stock</th>
              <th>Période</th>
              <th>Statut</th>
              <th class="text-end">Total Entrées (kg)</th>
              <th class="text-end">Sorties (kg)</th>
              <th class="text-end">Disponible (kg)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stocks as $stock)
              @php
                $totalEntrees = $stock->entrees->sum('quantite');
                $sorties = 0; // À calculer selon les tickets
                $disponible = $totalEntrees - $sorties;
              @endphp
              <tr>
                <td>
                  <a href="{{ route('stocks_pgf.show', $stock->id) }}" class="fw-bold text-primary">
                    {{ $stock->code }}
                  </a>
                </td>
                <td>
                  {{ $stock->date_debut ? $stock->date_debut->format('d-m-Y') : '-' }}
                  @if($stock->date_fin)
                    - {{ $stock->date_fin->format('d-m-Y') }}
                  @endif
                </td>
                <td>
                  @if($stock->statut === 'actif')
                    <span class="badge bg-success">Actif</span>
                  @else
                    <span class="badge bg-secondary">Clôturé</span>
                  @endif
                </td>
                <td class="text-end">{{ number_format($totalEntrees, 0, ',', ' ') }}</td>
                <td class="text-end text-danger">{{ number_format($sorties, 0, ',', ' ') }}</td>
                <td class="text-end fw-bold text-success">{{ number_format($disponible, 0, ',', ' ') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Aucun stock actif</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Générer Bordereau -->
<div class="modal fade" id="modalGenererBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-file me-2"></i>Générer un bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('stocks_pgf.bordereau.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pont <span class="text-danger">*</span></label>
            <select name="ponts[]" class="form-select" required>
              <option value="">-- Sélectionner un pont --</option>
              @foreach($ponts as $pont)
                <option value="{{ $pont['id_pont'] }}">{{ $pont['nom_pont'] }} ({{ $pont['code_pont'] }})</option>
              @endforeach
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début <span class="text-danger">*</span></label>
              <input type="date" name="date_debut" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin <span class="text-danger">*</span></label>
              <input type="date" name="date_fin" class="form-control" value="{{ date('Y-m-d') }}" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Générer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
