@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('stocks_pgf.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux stocks
        </a>
        <h4 class="mb-0">
          <i class="bx bx-package text-primary me-2"></i>
          Stock {{ $stock->code }}
        </h4>
        <small class="text-muted">
          Du {{ $stock->date_debut ? $stock->date_debut->format('d-m-Y') : '-' }}
          @if($stock->date_fin)
            au {{ $stock->date_fin->format('d-m-Y') }}
          @endif
        </small>
      </div>
      @if($stock->statut === 'actif')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddEntree">
          <i class="bx bx-plus me-1"></i>Ajouter une entrée
        </button>
      @endif
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

    <div class="row">
      <!-- Informations du stock -->
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label text-muted">Code</label>
              <p class="fw-bold mb-0">{{ $stock->code }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Statut</label>
              <p class="mb-0">
                @if($stock->statut === 'actif')
                  <span class="badge bg-success">Actif</span>
                @else
                  <span class="badge bg-secondary">Clôturé</span>
                @endif
              </p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Date début</label>
              <p class="fw-bold mb-0">{{ $stock->date_debut ? $stock->date_debut->format('d-m-Y') : '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Date fin</label>
              <p class="fw-bold mb-0">{{ $stock->date_fin ? $stock->date_fin->format('d-m-Y') : '-' }}</p>
            </div>
            <div class="mb-0">
              <label class="form-label text-muted">Total Entrées</label>
              <p class="fw-bold mb-0 text-success fs-4">{{ number_format($totalEntrees, 0, ',', ' ') }} kg</p>
            </div>
          </div>
        </div>

        <!-- Résumé par pont -->
        @if($entreesParPont->count() > 0)
          <div class="card mb-4">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0 text-white"><i class="bx bx-map me-2"></i>Entrées par pont</h5>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                @foreach($entreesParPont as $idPont => $data)
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <strong>{{ $data['nom_pont'] ?: 'Pont #' . $idPont }}</strong>
                      <br><small class="text-muted">{{ $data['nb_entrees'] }} entrée(s)</small>
                    </div>
                    <span class="badge bg-primary rounded-pill">{{ number_format($data['total'], 0, ',', ' ') }} kg</span>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        @endif
      </div>

      <!-- Liste des entrées -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-down-arrow-circle me-2"></i>Entrées de stock</h5>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Pont</th>
                  <th class="text-end">Quantité (kg)</th>
                  <th>Commentaire</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($stock->entrees->sortByDesc('date_entree') as $entree)
                  <tr>
                    <td>{{ $entree->date_entree ? $entree->date_entree->format('d-m-Y') : '-' }}</td>
                    <td>
                      <strong>{{ $entree->nom_pont ?: 'Pont #' . $entree->id_pont }}</strong>
                      @if($entree->code_pont)
                        <br><small class="text-muted">{{ $entree->code_pont }}</small>
                      @endif
                    </td>
                    <td class="text-end fw-bold text-success">{{ number_format($entree->quantite, 0, ',', ' ') }}</td>
                    <td>{{ $entree->commentaire ?: '-' }}</td>
                    <td class="text-center">
                      @if($stock->statut === 'actif')
                        <form method="POST" action="{{ route('stocks_pgf.entree.delete', ['id' => $stock->id, 'entree_id' => $entree->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer cette entrée ?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bx bx-trash"></i>
                          </button>
                        </form>
                      @else
                        -
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Aucune entrée enregistrée</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ajouter Entrée -->
<div class="modal fade" id="modalAddEntree" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-plus me-2"></i>Ajouter une entrée</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('stocks_pgf.entree.add', $stock->id) }}" id="formAddEntree">
        @csrf
        <input type="hidden" name="id_pont" id="id_pont_hidden" />
        <input type="hidden" name="pont_display" id="pont_display_hidden" />
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pont <span class="text-danger">*</span></label>
            <input type="text" id="pont_input" class="form-control" placeholder="Tapez pour rechercher un pont..." list="ponts_list" autocomplete="off" required />
            <datalist id="ponts_list">
              @foreach($ponts as $pont)
                <option data-id="{{ $pont['id_pont'] }}" value="{{ $pont['nom_pont'] }} ({{ $pont['code_pont'] }})">
              @endforeach
            </datalist>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantité (kg) <span class="text-danger">*</span></label>
            <input type="number" name="quantite" class="form-control" placeholder="Ex: 50000" min="0" step="0.01" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Date d'entrée <span class="text-danger">*</span></label>
            <input type="date" name="date_entree" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Commentaire</label>
            <textarea name="commentaire" class="form-control" rows="2" placeholder="Optionnel..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var pontsMap = {
    @foreach($ponts as $pont)
      "{{ $pont['nom_pont'] }} ({{ $pont['code_pont'] }})": {{ $pont['id_pont'] }},
    @endforeach
  };

  var pontInput = document.getElementById('pont_input');
  var idPontHidden = document.getElementById('id_pont_hidden');
  var pontDisplayHidden = document.getElementById('pont_display_hidden');

  if (pontInput) {
    pontInput.addEventListener('change', function() {
      var val = this.value;
      idPontHidden.value = pontsMap[val] || '';
      pontDisplayHidden.value = val;
    });
    pontInput.addEventListener('input', function() {
      var val = this.value;
      idPontHidden.value = pontsMap[val] || '';
      pontDisplayHidden.value = val;
    });
  }

  var formAddEntree = document.getElementById('formAddEntree');
  if (formAddEntree) {
    formAddEntree.addEventListener('submit', function(e) {
      if (!idPontHidden.value) {
        e.preventDefault();
        alert('Veuillez sélectionner un pont valide dans la liste.');
        pontInput.focus();
        return false;
      }
    });
  }
});
</script>
@endsection
