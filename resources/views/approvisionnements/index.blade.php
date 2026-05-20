@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-wallet text-warning me-2"></i>Approvisionnements</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelAppro">
        <i class="bx bx-plus me-1"></i> Nouvel approvisionnement
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Total approvisionnements</h6>
                <h3 class="mb-0 text-white">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</h3>
              </div>
              <div class="avatar avatar-lg bg-white bg-opacity-25">
                <i class="bx bx-wallet fs-3 text-white"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Ce mois</h6>
                <h3 class="mb-0 text-white">{{ number_format($approvisionnementsMois, 0, ',', ' ') }} FCFA</h3>
              </div>
              <div class="avatar avatar-lg bg-white bg-opacity-25">
                <i class="bx bx-calendar fs-3 text-white"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Nombre d'opérations</h6>
                <h3 class="mb-0 text-white">{{ $totalApprovisionnements }}</h3>
              </div>
              <div class="avatar avatar-lg bg-white bg-opacity-25">
                <i class="bx bx-transfer fs-3 text-white"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <form method="GET" action="{{ route('approvisionnements.index') }}" class="row g-3">
          <div class="col-md-3">
            <input type="text" name="pont" class="form-control" placeholder="Rechercher un pont..." value="{{ request('pont') }}">
          </div>
          <div class="col-md-3">
            <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}" placeholder="Date début">
          </div>
          <div class="col-md-3">
            <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}" placeholder="Date fin">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-outline-primary me-2"><i class="bx bx-search"></i> Filtrer</button>
            <a href="{{ route('approvisionnements.index') }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Pont</th>
              <th class="text-end">Montant</th>
              <th>Mode paiement</th>
              <th>Référence</th>
              <th>Commentaire</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($approvisionnements as $appro)
              <tr>
                <td>{{ $appro->date_approvisionnement->format('d/m/Y') }}</td>
                <td>
                  <strong>{{ $appro->nom_pont }}</strong>
                  @if($appro->code_pont)
                    <br><small class="text-muted">{{ $appro->code_pont }}</small>
                  @endif
                </td>
                <td class="text-end fw-bold text-success">{{ number_format($appro->montant, 0, ',', ' ') }} FCFA</td>
                <td>{{ $appro->mode_paiement ?? '-' }}</td>
                <td>{{ $appro->reference ?? '-' }}</td>
                <td>{{ Str::limit($appro->commentaire, 30) ?? '-' }}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $appro->id }}">
                    <i class="bx bx-edit"></i>
                  </button>
                  <form class="d-inline" method="POST" action="{{ route('approvisionnements.destroy', $appro) }}" onsubmit="return confirm('Supprimer cet approvisionnement ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4">
                  <i class="bx bx-info-circle fs-3 text-muted"></i>
                  <p class="text-muted mb-0">Aucun approvisionnement enregistré</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $approvisionnements->links() }}
    </div>
  </div>
</div>

<!-- Modal Nouvel Approvisionnement -->
<div class="modal fade" id="modalNouvelAppro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('approvisionnements.store') }}" id="formNouvelAppro">
        @csrf
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white"><i class="bx bx-plus-circle me-2"></i>Nouvel approvisionnement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pont <span class="text-danger">*</span></label>
            <select name="pont_id" class="form-select" required>
              <option value="">-- Sélectionner un pont --</option>
              @foreach($ponts as $pont)
                <option value="{{ $pont['id_pont'] }}">{{ $pont['nom_pont'] }} ({{ $pont['code_pont'] }})</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="text" id="montant_display" class="form-control montant-input" required placeholder="Ex: 5 000 000">
            <input type="hidden" name="montant" id="montant_hidden">
          </div>
          <div class="mb-3">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="date_approvisionnement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select" id="mode_paiement_new" onchange="togglePaiementFields('new')">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Banque">Banque (Virement)</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>
          <div class="mb-3" id="banque_field_new" style="display: none;">
            <label class="form-label">Nom de la banque</label>
            <input type="text" name="nom_banque" class="form-control" placeholder="Ex: SGBCI, BICICI, NSIA...">
          </div>
          <div class="mb-3" id="cheque_field_new" style="display: none;">
            <label class="form-label">Numéro du chèque</label>
            <input type="text" name="numero_cheque" class="form-control" placeholder="Ex: 0012345678">
          </div>
          <div class="mb-3" id="mobile_field_new" style="display: none;">
            <label class="form-label">Opérateur</label>
            <select name="operateur" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="Orange Money">Orange Money</option>
              <option value="MTN Mobile Money">MTN Mobile Money</option>
              <option value="Moov Money">Moov Money</option>
              <option value="Wave">Wave</option>
            </select>
          </div>
          <div class="mb-3" id="reference_field_new">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" placeholder="N° de transaction...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals Edit -->
@foreach($approvisionnements as $appro)
<div class="modal fade" id="modalEdit{{ $appro->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('approvisionnements.update', $appro) }}" class="form-edit-appro">
        @csrf
        @method('PUT')
        <div class="modal-header bg-warning">
          <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Modifier l'approvisionnement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pont</label>
            <input type="text" class="form-control" value="{{ $appro->nom_pont }} ({{ $appro->code_pont }})" disabled>
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="text" class="form-control montant-input" required data-target="montant_hidden_{{ $appro->id }}" value="{{ number_format($appro->montant, 0, '', ' ') }}">
            <input type="hidden" name="montant" id="montant_hidden_{{ $appro->id }}" value="{{ $appro->montant }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="date_approvisionnement" class="form-control" required value="{{ $appro->date_approvisionnement->format('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select mode-paiement-edit" data-id="{{ $appro->id }}" onchange="togglePaiementFieldsEdit({{ $appro->id }})">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces" {{ $appro->mode_paiement == 'Espèces' ? 'selected' : '' }}>Espèces</option>
              <option value="Banque" {{ $appro->mode_paiement == 'Banque' ? 'selected' : '' }}>Banque (Virement)</option>
              <option value="Chèque" {{ $appro->mode_paiement == 'Chèque' ? 'selected' : '' }}>Chèque</option>
              <option value="Mobile Money" {{ $appro->mode_paiement == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
            </select>
          </div>
          <div class="mb-3" id="banque_field_{{ $appro->id }}" style="{{ $appro->mode_paiement == 'Banque' ? '' : 'display: none;' }}">
            <label class="form-label">Nom de la banque</label>
            <input type="text" name="nom_banque" class="form-control" value="{{ $appro->nom_banque }}" placeholder="Ex: SGBCI, BICICI, NSIA...">
          </div>
          <div class="mb-3" id="cheque_field_{{ $appro->id }}" style="{{ $appro->mode_paiement == 'Chèque' ? '' : 'display: none;' }}">
            <label class="form-label">Numéro du chèque</label>
            <input type="text" name="numero_cheque" class="form-control" value="{{ $appro->numero_cheque }}" placeholder="Ex: 0012345678">
          </div>
          <div class="mb-3" id="mobile_field_{{ $appro->id }}" style="{{ $appro->mode_paiement == 'Mobile Money' ? '' : 'display: none;' }}">
            <label class="form-label">Opérateur</label>
            <select name="operateur" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="Orange Money" {{ $appro->operateur == 'Orange Money' ? 'selected' : '' }}>Orange Money</option>
              <option value="MTN Mobile Money" {{ $appro->operateur == 'MTN Mobile Money' ? 'selected' : '' }}>MTN Mobile Money</option>
              <option value="Moov Money" {{ $appro->operateur == 'Moov Money' ? 'selected' : '' }}>Moov Money</option>
              <option value="Wave" {{ $appro->operateur == 'Wave' ? 'selected' : '' }}>Wave</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" value="{{ $appro->reference }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning"><i class="bx bx-save me-1"></i>Modifier</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<script>
// Formatage du montant avec séparateurs de milliers
function formatMontant(input) {
  let value = input.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
  if (value) {
    input.value = parseInt(value).toLocaleString('fr-FR').replace(/,/g, ' ');
  }
}

// Gestion des champs conditionnels pour le nouveau formulaire
function togglePaiementFields(suffix) {
  var mode = document.getElementById('mode_paiement_' + suffix).value;
  
  document.getElementById('banque_field_' + suffix).style.display = mode === 'Banque' ? 'block' : 'none';
  document.getElementById('cheque_field_' + suffix).style.display = mode === 'Chèque' ? 'block' : 'none';
  document.getElementById('mobile_field_' + suffix).style.display = mode === 'Mobile Money' ? 'block' : 'none';
}

// Gestion des champs conditionnels pour les formulaires d'édition
function togglePaiementFieldsEdit(id) {
  var select = document.querySelector('.mode-paiement-edit[data-id="' + id + '"]');
  var mode = select.value;
  
  document.getElementById('banque_field_' + id).style.display = mode === 'Banque' ? 'block' : 'none';
  document.getElementById('cheque_field_' + id).style.display = mode === 'Chèque' ? 'block' : 'none';
  document.getElementById('mobile_field_' + id).style.display = mode === 'Mobile Money' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
  // Formatage du montant pour le nouveau formulaire
  var montantDisplay = document.getElementById('montant_display');
  var montantHidden = document.getElementById('montant_hidden');
  
  if (montantDisplay) {
    montantDisplay.addEventListener('input', function() {
      formatMontant(this);
      montantHidden.value = this.value.replace(/\s/g, '');
    });
  }

  // Formatage du montant pour les formulaires d'édition
  document.querySelectorAll('.montant-input').forEach(function(input) {
    input.addEventListener('input', function() {
      formatMontant(this);
      var targetId = this.dataset.target;
      if (targetId) {
        document.getElementById(targetId).value = this.value.replace(/\s/g, '');
      }
    });
  });

  // Soumission du formulaire de création
  var formNouvelAppro = document.getElementById('formNouvelAppro');
  if (formNouvelAppro) {
    formNouvelAppro.addEventListener('submit', function() {
      montantHidden.value = montantDisplay.value.replace(/\s/g, '');
    });
  }
});
</script>

@endsection
