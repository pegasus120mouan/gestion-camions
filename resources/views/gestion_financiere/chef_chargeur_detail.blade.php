@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière - {{ $chef->nom }} {{ $chef->prenoms }}</h4>
        @if($chef->contact)
          <span class="badge bg-secondary">{{ $chef->contact }}</span>
        @endif
      </div>
      <div>
        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#modalPrintHistorique">
          <i class="bx bx-printer me-1"></i>Imprimer Historique
        </button>
        <a href="{{ route('gestionfinanciere.montant_chef_chargeur') }}" class="btn btn-secondary">
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
      <div class="col-md-6">
        <div class="card">
          <div class="card-header" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
            <h5 class="card-title mb-0" style="color: #842029;"><i class="bx bx-minus-circle me-2"></i>Montant ({{ count($fichesAvecMontant) }})</h5>
          </div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Véhicule</th>
                  <th>Poids</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($fichesAvecMontant as $item)
                  <tr>
                    <td>{{ $item['fiche']->date_chargement ? \Carbon\Carbon::parse($item['fiche']->date_chargement)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item['fiche']->matricule ?? '-' }}</td>
                    <td>{{ number_format($item['fiche']->poids_pont, 0, ',', ' ') }} Kg</td>
                    <td class="text-end text-danger">{{ number_format($item['montant'], 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center">Aucune fiche</td>
                  </tr>
                @endforelse
              </tbody>
              @if(count($fichesAvecMontant) > 0)
                <tfoot>
                  <tr class="table-warning">
                    <td colspan="3"><strong>Total</strong></td>
                    <td class="text-end"><strong>{{ number_format($montantDu, 0, ',', ' ') }} FCFA</strong></td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #d1e7dd; border-bottom: 1px solid #badbcc;">
            <h5 class="card-title mb-0" style="color: #0f5132;"><i class="bx bx-plus-circle me-2"></i>Paiement ({{ $paiements->count() }})</h5>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiement">
              <i class="bx bx-plus"></i> Ajouter
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Mode</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($paiements as $paiement)
                  <tr>
                    <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}</td>
                    <td>
                      @if($paiement->mode_paiement)
                        <span class="badge bg-info">{{ $paiement->mode_paiement }}</span>
                      @else
                        -
                      @endif
                    </td>
                    <td class="text-end text-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
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
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-end"><strong>{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</strong></td>
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

<!-- Modal Paiement -->
<div class="modal fade" id="modalPaiement" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Nouveau paiement - {{ $chef->nom }} {{ $chef->prenoms }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('gestionfinanciere.paiement_chef_chargeur.store', $chef) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Reste à payer:</strong> {{ number_format($resteAPayer, 0, ',', ' ') }} FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant</label>
            <input type="text" id="montant_display" class="form-control" required placeholder="0">
            <input type="hidden" name="montant" id="montant_hidden">
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" id="mode_paiement" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
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
      <form method="GET" action="{{ route('gestionfinanciere.chef_chargeur.pdf', ['id' => $chef->id]) }}" target="_blank">
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
  var montantDisplay = document.getElementById('montant_display');
  var montantHidden = document.getElementById('montant_hidden');
  
  if (montantDisplay) {
    montantDisplay.addEventListener('input', function(e) {
      var value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
      if (value) {
        montantHidden.value = value;
        e.target.value = parseInt(value, 10).toLocaleString('fr-FR').replace(/,/g, ' ');
      } else {
        montantHidden.value = '';
        e.target.value = '';
      }
    });
  }

  var modePaiement = document.getElementById('mode_paiement');
  var refContainer = document.getElementById('reference_container');
  if (modePaiement && refContainer) {
    modePaiement.addEventListener('change', function() {
      if (this.value === 'Chèque' || this.value === 'Virement') {
        refContainer.style.display = 'block';
      } else {
        refContainer.style.display = 'none';
      }
    });
  }
});
</script>
@endsection
