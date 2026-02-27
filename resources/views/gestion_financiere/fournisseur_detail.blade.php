@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière - {{ $fournisseurNom }}</h4>
        @if($service)
          <span class="badge bg-primary">{{ $service->nom_service }}</span>
        @endif
      </div>
      <div>
        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#modalPrintHistorique">
          <i class="bx bx-printer me-1"></i>Imprimer Historique
        </button>
        <a href="{{ route('depenses.liste') }}" class="btn btn-secondary">
          <i class="bx bx-arrow-back me-1"></i>Retour
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <!-- Résumé financier -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">Montant Dû</h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
          <div class="card-body">
            <h6 class="card-title" style="color: #0f5132;">Montant Payé</h6>
            <h3 class="mb-0" style="color: #0f5132;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
          <div class="card-body">
            <h6 class="card-title" style="color: #664d03;">Reste à Payer</h6>
            <h3 class="mb-0" style="color: #664d03;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Liste des dépenses -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
            <h5 class="card-title mb-0" style="color: #842029;"><i class="bx bx-minus-circle me-2"></i>Dépenses ({{ $depenses->count() }})</h5>
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Véhicule</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($depenses as $depense)
                  <tr>
                    <td>{{ $depense->date_depense ? $depense->date_depense->format('d-m-Y') : '-' }}</td>
                    <td>{{ $depense->matricule_vehicule }}</td>
                    <td class="text-danger fw-bold">{{ number_format($depense->montant, 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center">Aucune dépense</td>
                  </tr>
                @endforelse
              </tbody>
              @if($depenses->count() > 0)
                <tfoot>
                  <tr class="table-danger">
                    <td colspan="2" class="fw-bold">Total</td>
                    <td class="fw-bold">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>

      <!-- Liste des paiements -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #d1e7dd; border-bottom: 1px solid #badbcc;">
            <h5 class="card-title mb-0" style="color: #0f5132;"><i class="bx bx-plus-circle me-2"></i>Paiements ({{ $paiements->count() }})</h5>
            @if($fournisseur && $resteAPayer > 0)
              <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalPaiement">
                <i class="bx bx-plus"></i> Ajouter
              </button>
            @endif
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Mode</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($paiements as $paiement)
                  <tr>
                    <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d-m-Y') : '-' }}</td>
                    <td>
                      @if($paiement->mode_paiement == 'especes')
                        <span class="badge bg-success">Espèces</span>
                      @elseif($paiement->mode_paiement == 'virement')
                        <span class="badge bg-info">Virement</span>
                      @elseif($paiement->mode_paiement == 'cheque')
                        <span class="badge bg-warning">Chèque</span>
                      @else
                        <span class="badge bg-secondary">{{ $paiement->mode_paiement }}</span>
                      @endif
                    </td>
                    <td class="text-success fw-bold">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center">Aucun paiement</td>
                  </tr>
                @endforelse
              </tbody>
              @if($paiements->count() > 0)
                <tfoot>
                  <tr class="table-success">
                    <td colspan="2" class="fw-bold">Total</td>
                    <td class="fw-bold">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@if($fournisseur)
<!-- Modal Paiement -->
<div class="modal fade" id="modalPaiement" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Nouveau paiement - {{ $fournisseurNom }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('gestionfinanciere.montant_fournisseur.paiement') }}" method="POST">
        @csrf
        <input type="hidden" name="fournisseur_id" value="{{ $fournisseur->id }}">
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Reste à payer:</strong> {{ number_format($resteAPayer, 0, ',', ' ') }} FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant</label>
            <input type="text" name="montant" id="montant_input" class="form-control" required placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" id="mode_paiement" class="form-select" required>
              <option value="especes">Espèces</option>
              <option value="virement">Virement</option>
              <option value="cheque">Chèque</option>
            </select>
          </div>
          <div class="mb-3" id="reference_container" style="display: none;">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" placeholder="Numéro de chèque ou référence">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Imprimer Historique -->
<div class="modal fade" id="modalPrintHistorique" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-printer me-2"></i>Imprimer Historique</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formPrintHistorique" method="GET" action="{{ route('gestionfinanciere.fournisseur.pdf', ['nom' => $fournisseurNom]) }}" target="_blank">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger"><i class="bx bx-printer me-1"></i>Imprimer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Formatage du montant
  var montantInput = document.getElementById('montant_input');
  if (montantInput) {
    montantInput.addEventListener('input', function(e) {
      var value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
      if (value) {
        e.target.value = parseInt(value, 10).toLocaleString('fr-FR').replace(/,/g, ' ');
      }
    });

    montantInput.form.addEventListener('submit', function() {
      montantInput.value = montantInput.value.replace(/\s/g, '');
    });
  }

  // Affichage conditionnel de la référence
  var modePaiement = document.getElementById('mode_paiement');
  var refContainer = document.getElementById('reference_container');
  if (modePaiement && refContainer) {
    modePaiement.addEventListener('change', function() {
      if (this.value === 'cheque' || this.value === 'virement') {
        refContainer.style.display = 'block';
      } else {
        refContainer.style.display = 'none';
      }
    });
  }
});
</script>
@endif
@endsection
