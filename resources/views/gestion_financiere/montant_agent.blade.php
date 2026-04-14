@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Gestion financière /</span> Montant Pisteur
    </h4>

    @if(!empty($external_error))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $external_error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-user-check me-2"></i>Liste des agents</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th class="text-uppercase small text-muted">Agent</th>
              <th class="text-end text-uppercase small text-muted">Montant dû</th>
              <th class="text-end text-uppercase small text-muted">Montant payé</th>
              <th class="text-end text-uppercase small text-muted">Reste à payer</th>
              <th class="text-center text-uppercase small text-muted">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $item)
              @php
                $idAgent = $item['agent']['id_agent'] ?? 0;
                $nomComplet = $item['agent']['nom_complet'] ?? (($item['agent']['nom_agent'] ?? '') . ' ' . ($item['agent']['prenom_agent'] ?? ''));
                $numeroAgent = $item['agent']['numero_agent'] ?? '';
              @endphp
              <tr>
                <td>
                  <a href="{{ route('gestionfinanciere.agent.show', ['id_agent' => $idAgent]) }}" class="text-primary fw-bold">
                    {{ trim($nomComplet) ?: '—' }}
                  </a>
                  @if($numeroAgent !== '')
                    <br><small class="text-muted">{{ $numeroAgent }}</small>
                  @endif
                </td>
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
                  <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiementAgent{{ $idAgent }}">
                    <i class="bx bx-plus"></i> Paiement
                  </button>
                  <a href="{{ route('agents.show', ['id_agent' => $idAgent]) }}" class="btn btn-sm btn-outline-primary" title="Fiche agent et tarifs">
                    <i class="bx bx-show"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Aucun agent à afficher</td>
              </tr>
            @endforelse
          </tbody>
          @if(count($data) > 0)
          <tfoot class="table-light">
            <tr>
              <th class="text-end">TOTAUX</th>
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
  document.querySelectorAll('.montant-input-agent').forEach(function(input) {
    var hiddenInput = input.closest('form').querySelector('.montant-hidden-agent');
    input.addEventListener('input', function() {
      var value = this.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
      if (value) {
        hiddenInput.value = value;
        this.value = parseInt(value, 10).toLocaleString('fr-FR').replace(/,/g, ' ');
      } else {
        hiddenInput.value = '';
        this.value = '';
      }
    });
  });

  document.querySelectorAll('.mode-paiement-agent').forEach(function(select) {
    var referenceField = select.closest('form').querySelector('.reference-field-agent');
    if (!referenceField) return;
    select.addEventListener('change', function() {
      if (this.value === 'Chèque') {
        referenceField.style.display = 'block';
      } else {
        referenceField.style.display = 'none';
        var inp = referenceField.querySelector('input');
        if (inp) inp.value = '';
      }
    });
  });
});
</script>

@foreach($data as $item)
@php $idAgent = $item['agent']['id_agent'] ?? 0; @endphp
@if($idAgent)
<div class="modal fade" id="modalPaiementAgent{{ $idAgent }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enregistrer un paiement — {{ $item['agent']['nom_complet'] ?? 'Agent' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('gestionfinanciere.paiement_agent.store', ['id_agent' => $idAgent]) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info mb-3">
            <small>
              <strong>Reste à payer:</strong> {{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="text" class="form-control montant-input-agent" placeholder="0" required />
            <input type="hidden" name="montant" class="montant-hidden-agent" />
          </div>

          <div class="mb-3">
            <label class="form-label">Date de paiement <span class="text-danger">*</span></label>
            <input type="date" name="date_paiement" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>

          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select mode-paiement-agent">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>

          <div class="mb-3 reference-field-agent" style="display: none;">
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
@endif
@endforeach
@endsection
