@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Gestion financière /</span> Montant Chef Chargeur
    </h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-user-check me-2"></i>Liste des Chefs des Chargeurs</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Chef des chargeurs</th>
              <th>Contact</th>
              <th class="text-end">Montant dû</th>
              <th class="text-end">Montant payé</th>
              <th class="text-end">Reste à payer</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $item)
              <tr>
                <td>
                  <strong>{{ $item['chef']->nom }} {{ $item['chef']->prenoms }}</strong>
                </td>
                <td>{{ $item['chef']->contact ?? '-' }}</td>
                <td class="text-end">
                  <span class="text-primary fw-bold">{{ number_format($item['montant_du'], 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-end">
                  <span class="text-success">{{ number_format($item['montant_paye'], 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-end">
                  @if($item['reste_a_payer'] > 0)
                    <span class="text-danger fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @elseif($item['reste_a_payer'] < 0)
                    <span class="text-warning fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-success"><i class="bx bx-check-circle"></i> Soldé</span>
                  @endif
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiement{{ $item['chef']->id }}">
                    <i class="bx bx-plus"></i> Paiement
                  </button>
                  <a href="{{ route('chef_chargeurs.show', $item['chef']) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-show"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Aucun chef des chargeurs enregistré</td>
              </tr>
            @endforelse
          </tbody>
          @if(count($data) > 0)
          <tfoot class="table-light">
            <tr>
              <th colspan="2" class="text-end">TOTAUX</th>
              <th class="text-end text-primary fw-bold">{{ number_format(collect($data)->sum('montant_du'), 0, ',', ' ') }} FCFA</th>
              <th class="text-end text-success">{{ number_format(collect($data)->sum('montant_paye'), 0, ',', ' ') }} FCFA</th>
              <th class="text-end text-danger fw-bold">{{ number_format(collect($data)->sum('reste_a_payer'), 0, ',', ' ') }} FCFA</th>
              <th></th>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Formatage du montant avec espaces
  document.querySelectorAll('.montant-input').forEach(function(input) {
    var hiddenInput = input.closest('form').querySelector('.montant-hidden');
    
    input.addEventListener('input', function(e) {
      var value = this.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
      if (value) {
        hiddenInput.value = value;
        this.value = parseInt(value).toLocaleString('fr-FR').replace(/,/g, ' ');
      } else {
        hiddenInput.value = '';
        this.value = '';
      }
    });
  });

  // Afficher/masquer le champ référence selon le mode de paiement
  document.querySelectorAll('.mode-paiement-select').forEach(function(select) {
    var referenceField = select.closest('form').querySelector('.reference-field');
    
    select.addEventListener('change', function() {
      if (this.value === 'Chèque') {
        referenceField.style.display = 'block';
      } else {
        referenceField.style.display = 'none';
        referenceField.querySelector('input').value = '';
      }
    });
  });
});
</script>

@foreach($data as $item)
<!-- Modal Paiement -->
<div class="modal fade" id="modalPaiement{{ $item['chef']->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enregistrer un paiement - {{ $item['chef']->nom }} {{ $item['chef']->prenoms }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('gestionfinanciere.paiement_chef_chargeur.store', $item['chef']) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info mb-3">
            <small>
              <strong>Reste à payer:</strong> {{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="text" class="form-control montant-input" placeholder="0" required />
            <input type="hidden" name="montant" class="montant-hidden" />
          </div>

          <div class="mb-3">
            <label class="form-label">Date de paiement <span class="text-danger">*</span></label>
            <input type="date" name="date_paiement" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>

          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select mode-paiement-select">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>

          <div class="mb-3 reference-field" style="display: none;">
            <label class="form-label">N° Chèque</label>
            <input type="text" name="reference" class="form-control" placeholder="Numéro du chèque..." />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">
            <i class="bx bx-check me-1"></i> Enregistrer le paiement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach
@endsection
