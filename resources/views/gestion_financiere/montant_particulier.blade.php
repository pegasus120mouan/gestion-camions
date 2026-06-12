@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Gestion financière /</span> Montant Agents Particuliers
    </h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @include('gestion_financiere._filtres_montant_agent', [
      'actionRoute' => route('gestionfinanciere.montant_particulier'),
      'filtres' => $filtres,
      'filtresActifs' => $filtresActifs,
      'produits' => $produits,
      'usines' => $usines,
      'showSearch' => true,
      'search' => $search,
      'agentNoms' => $agentNoms,
      'showSyntheseLink' => false,
    ])

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-user-check me-2"></i>Liste des agents locaux</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th class="text-uppercase small text-muted">Agent</th>
              <th class="text-end text-uppercase small text-muted">
                Montant dû
                @if(!empty($filtresActifs))
                  <br><small class="fw-normal">(filtre)</small>
                @endif
              </th>
              <th class="text-end text-uppercase small text-muted">Montant payé</th>
              <th class="text-end text-uppercase small text-muted">Reste à payer</th>
              <th class="text-center text-uppercase small text-muted">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $item)
              @php
                $agent = $item['agent'];
                $lienAgent = route('gestionfinanciere.particulier.show', array_merge(['agent' => $agent->id], array_filter([
                  'produit_id' => $filtres['produit_id'] ?? null,
                  'usine' => $filtres['usine'] ?? null,
                  'date_debut' => $filtres['date_debut'] ?? null,
                  'date_fin' => $filtres['date_fin'] ?? null,
                ])));
              @endphp
              <tr>
                <td>
                  <a href="{{ $lienAgent }}" class="text-primary fw-bold">{{ $agent->nom_complet }}</a>
                  @if($agent->numero_agent)
                    <br><small class="text-muted">{{ $agent->numero_agent }}</small>
                  @endif
                  @if($agent->groupe)
                    <br><small class="text-muted">{{ $agent->groupe->nom_groupe }}</small>
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
                  <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiementParticulier{{ $agent->id }}">
                    <i class="bx bx-plus"></i> Paiement
                  </button>
                  <a href="{{ route('particuliers.prix.show', $agent) }}" class="btn btn-sm btn-outline-primary" title="Prix unitaires">
                    <i class="bx bx-show"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  @if(!empty($search))
                    Aucun agent trouvé pour « {{ $search }} »
                  @else
                    Aucun agent local à afficher
                  @endif
                </td>
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
  document.querySelectorAll('.montant-input-particulier').forEach(function(input) {
    var hiddenInput = input.closest('form').querySelector('.montant-hidden-particulier');
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
});
</script>

@foreach($data as $item)
@php $agent = $item['agent']; @endphp
<div class="modal fade" id="modalPaiementParticulier{{ $agent->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enregistrer un paiement — {{ $agent->nom_complet }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('gestionfinanciere.paiement_particulier.store', $agent) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info mb-3">
            <small><strong>Reste à payer:</strong> {{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="text" class="form-control montant-input-particulier" placeholder="0" required />
            <input type="hidden" name="montant" class="montant-hidden-particulier" />
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement <span class="text-danger">*</span></label>
            <input type="date" name="date_paiement" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@include('gestion_financiere._filtres_montant_agent_js')
@endsection
