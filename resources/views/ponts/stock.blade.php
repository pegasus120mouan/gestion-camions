@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header avec infos du pont -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('ponts.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux ponts
        </a>
        <h4 class="mb-0">
          <i class="bx bx-package text-primary me-2"></i>
          Gestion du Stock - {{ $pont['nom_pont'] ?? 'Pont' }}
        </h4>
        <small class="text-muted">Code: {{ $pont['code_pont'] ?? '-' }} | Gérant: {{ $pont['gerant'] ?? '-' }}</small>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
        <i class="bx bx-plus me-1"></i> Ajouter un stock
      </button>
    </div>

    @if(!empty($external_error))
      <div class="alert alert-danger">{{ $external_error }}</div>
    @endif

    <!-- Résumé du stock -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Cumul des Entrées</h6>
                <h3 class="mb-0">{{ number_format($stockTotal ?? 0, 0, ',', ' ') }} kg</h3>
              </div>
              <i class="bx bx-package" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Cumul des sorties</h6>
                <h3 class="mb-0">{{ number_format($totalEntrees ?? 0, 0, ',', ' ') }} kg</h3>
              </div>
              <i class="bx bx-down-arrow-circle" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Ecart</h6>
                <h3 class="mb-0">{{ number_format(($totalEntrees ?? 0) - ($stockTotal ?? 0), 0, ',', ' ') }} kg</h3>
              </div>
              <i class="bx bx-up-arrow-circle" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Stock disponible</h6>
                @php
                  if (($totalEntrees ?? 0) >= ($stockTotal ?? 0)) {
                    $stockDisponible = 0;
                  } else {
                    $stockDisponible = ($stockTotal ?? 0) - ($totalEntrees ?? 0);
                  }
                @endphp
                <h3 class="mb-0">{{ number_format($stockDisponible, 0, ',', ' ') }} kg</h3>
              </div>
              <i class="bx bx-transfer" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tableau des mouvements de stock -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Mouvements de stock</h5>
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm" style="width: auto;">
            <option value="">Tous les types</option>
            <option value="entree">Entrées</option>
            <option value="sortie">Sorties</option>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Quantité (kg)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stocks as $s)
              <tr>
                <td>{{ $s->date_mouvement ? $s->date_mouvement->format('d-m-Y') : '-' }}</td>
                <td>
                  @if($s->type === 'entree')
                    <span class="badge bg-success">Entrée</span>
                  @else
                    <span class="badge bg-warning">Sortie</span>
                  @endif
                </td>
                <td>{{ number_format((float)$s->quantite, 0, ',', ' ') }}</td>
                <td>
                  <button class="btn btn-sm btn-outline-danger" onclick="if(confirm('Supprimer ce mouvement?')) document.getElementById('delete-stock-{{ $s->id }}').submit();">
                    <i class="bx bx-trash"></i>
                  </button>
                  <form id="delete-stock-{{ $s->id }}" action="{{ route('ponts.stock.delete', ['id_pont' => $pont['id_pont'], 'stock_id' => $s->id]) }}" method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-4">
                  <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                  <p class="text-muted mt-2 mb-0">Aucun mouvement de stock enregistré</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Modal Ajouter Stock -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-plus-circle me-2"></i>Ajouter un mouvement de stock
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('ponts.stock.store', ['id_pont' => $pont['id_pont']]) }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Type de mouvement <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="Entrée" readonly />
            <input type="hidden" name="type" value="entree" />
          </div>
          <div class="mb-3">
            <label class="form-label">Quantité (kg) <span class="text-danger">*</span></label>
            <input type="number" name="quantite" class="form-control" placeholder="Ex: 5000" min="0" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
