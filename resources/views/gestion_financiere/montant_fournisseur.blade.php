@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="mb-4">Montant Fournisseur</h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Fournisseur</th>
              <th>Service</th>
              <th>Montant Dû</th>
              <th>Montant Payé</th>
              <th>Reste à Payer</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fournisseursData as $data)
              <tr>
                <td><strong>{{ $data['fournisseur']->nom }}</strong></td>
                <td>
                  <span class="badge bg-primary">{{ $data['fournisseur']->service->nom_service ?? '-' }}</span>
                </td>
                <td class="text-danger">{{ number_format($data['montant_du'], 0, ',', ' ') }} FCFA</td>
                <td class="text-success">{{ number_format($data['montant_paye'], 0, ',', ' ') }} FCFA</td>
                <td>
                  @if($data['reste_a_payer'] > 0)
                    <span class="text-warning fw-bold">{{ number_format($data['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-success fw-bold">0 FCFA</span>
                  @endif
                </td>
                <td>
                  @if($data['reste_a_payer'] > 0)
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalPaiement{{ $data['fournisseur']->id }}">
                      <i class="bx bx-money"></i> Payer
                    </button>
                  @else
                    <span class="badge bg-success">Soldé</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">Aucun fournisseur</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @php
      $totalDu = collect($fournisseursData)->sum('montant_du');
      $totalPaye = collect($fournisseursData)->sum('montant_paye');
      $totalReste = collect($fournisseursData)->sum('reste_a_payer');
    @endphp

    @if(count($fournisseursData) > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Résumé</h5>
          <div class="row">
            <div class="col-md-4">
              <p><strong>Total Dû:</strong> <span class="text-danger">{{ number_format($totalDu, 0, ',', ' ') }} FCFA</span></p>
            </div>
            <div class="col-md-4">
              <p><strong>Total Payé:</strong> <span class="text-success">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</span></p>
            </div>
            <div class="col-md-4">
              <p><strong>Total Reste:</strong> <span class="text-warning">{{ number_format($totalReste, 0, ',', ' ') }} FCFA</span></p>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>

<!-- Modals de paiement -->
@foreach($fournisseursData as $data)
  <div class="modal fade" id="modalPaiement{{ $data['fournisseur']->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement - {{ $data['fournisseur']->nom }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="{{ route('gestionfinanciere.montant_fournisseur.paiement') }}" method="POST">
          @csrf
          <input type="hidden" name="fournisseur_id" value="{{ $data['fournisseur']->id }}">
          <div class="modal-body">
            <div class="alert alert-info">
              <strong>Reste à payer:</strong> {{ number_format($data['reste_a_payer'], 0, ',', ' ') }} FCFA
            </div>
            <div class="mb-3">
              <label class="form-label">Montant</label>
              <input type="text" name="montant" id="montant_{{ $data['fournisseur']->id }}" class="form-control montant-input" required placeholder="0">
            </div>
            <div class="mb-3">
              <label class="form-label">Date de paiement</label>
              <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
            </div>
            <div class="mb-3">
              <label class="form-label">Mode de paiement</label>
              <select name="mode_paiement" class="form-select mode-paiement-select" data-fournisseur="{{ $data['fournisseur']->id }}" required>
                <option value="especes">Espèces</option>
                <option value="virement">Virement</option>
                <option value="cheque">Chèque</option>
              </select>
            </div>
            <div class="mb-3 reference-container" id="reference_container_{{ $data['fournisseur']->id }}" style="display: none;">
              <label class="form-label">Référence</label>
              <input type="text" name="reference" class="form-control" placeholder="Numéro de chèque ou référence">
            </div>
            <div class="mb-3">
              <label class="form-label">Commentaire</label>
              <textarea name="commentaire" class="form-control" rows="2"></textarea>
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
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Formatage des montants
  document.querySelectorAll('.montant-input').forEach(function(input) {
    input.addEventListener('input', function(e) {
      var value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
      if (value) {
        e.target.value = parseInt(value, 10).toLocaleString('fr-FR').replace(/,/g, ' ');
      }
    });

    input.form.addEventListener('submit', function() {
      input.value = input.value.replace(/\s/g, '');
    });
  });

  // Affichage conditionnel de la référence
  document.querySelectorAll('.mode-paiement-select').forEach(function(select) {
    select.addEventListener('change', function() {
      var fournisseurId = this.getAttribute('data-fournisseur');
      var refContainer = document.getElementById('reference_container_' + fournisseurId);
      if (this.value === 'cheque' || this.value === 'virement') {
        refContainer.style.display = 'block';
      } else {
        refContainer.style.display = 'none';
      }
    });
  });
});
</script>
@endsection
