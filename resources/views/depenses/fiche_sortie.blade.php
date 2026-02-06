@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Fiche de sortie</h4>
        @if(isset($vehicule) && is_array($vehicule))
          <p class="text-muted mb-0">Vehicule: {{ $vehicule['matricule_vehicule'] ?? '' }}</p>
        @endif
      </div>
      <div>
        @if(isset($fiche_sortie) && $fiche_sortie)
          @if($fiche_sortie->id_ticket)
            <span class="badge bg-success me-2">Ticket: {{ $fiche_sortie->numero_ticket }}</span>
          @else
            <button type="button" class="btn btn-outline-success me-2" data-bs-toggle="modal" data-bs-target="#modalAssocierTicket">
              <i class="bx bx-link"></i> Associer un ticket
            </button>
          @endif
        @endif
        <button onclick="window.print()" class="btn btn-outline-primary me-2">
          <i class="bx bx-printer"></i> Imprimer
        </button>
        <a href="{{ route('vehicules.depenses', ['vehicule_id' => $vehicule_id]) }}" class="btn btn-outline-secondary">
          <i class="bx bx-arrow-back"></i> Retour
        </a>
      </div>
    </div>

    <div class="card" id="fiche-sortie">
      <div class="card-header text-center border-bottom">
        <h3 class="mb-1">FICHE DE SORTIE</h3>
        <p class="mb-0 text-muted">Vehicule: <strong>{{ $vehicule['matricule_vehicule'] ?? '' }}</strong></p>
        <p class="mb-0 text-muted">Date: {{ request('date_chargement') ? \Carbon\Carbon::parse(request('date_chargement'))->format('d-m-Y') : now()->format('d-m-Y') }}</p>
      </div>
      <div class="card-body">
        <!-- Informations de chargement -->
        <div class="row mb-4 p-3 bg-light rounded">
          <div class="col-md-3">
            <p class="mb-1 text-muted small">Pont de pesage</p>
            <p class="mb-0 fw-bold">{{ $pont['nom_pont'] ?? '-' }}</p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small">Agent</p>
            <p class="mb-0 fw-bold">{{ $agent['nom_complet'] ?? '-' }}</p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small">Date de chargement</p>
            <p class="mb-0 fw-bold">{{ request('date_chargement') ? \Carbon\Carbon::parse(request('date_chargement'))->format('d-m-Y') : '-' }}</p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small">Poids sur le pont</p>
            <p class="mb-0 fw-bold">{{ request('poids_pont') ? number_format((float)request('poids_pont'), 0, ',', ' ') . ' kg' : '-' }}</p>
          </div>
        </div>

        <div class="row mt-5">
          <div class="col-6">
            <p class="mb-0"><strong>Signature du proprietaire:</strong></p>
            <div style="border-bottom: 1px solid #000; height: 50px; margin-top: 10px;"></div>
          </div>
          <div class="col-6">
            <p class="mb-0"><strong>Signature du chauffeur:</strong></p>
            <div style="border-bottom: 1px solid #000; height: 50px; margin-top: 10px;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Associer Ticket -->
@if(isset($fiche_sortie) && $fiche_sortie && !$fiche_sortie->id_ticket)
<div class="modal fade" id="modalAssocierTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Associer un ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('fiches_sortie.associer_ticket', ['fiche_id' => $fiche_sortie->id]) }}" id="formAssocierTicket">
          @csrf

          <div class="mb-3">
            <label class="form-label">Numero du ticket <span class="text-danger">*</span></label>
            <select name="id_ticket" id="id_ticket_select" class="form-select" required>
              <option value="">-- Selectionner un ticket --</option>
              @foreach($tickets ?? [] as $ticket)
                <option value="{{ $ticket['id_ticket'] }}" data-numero="{{ $ticket['numero_ticket'] }}">{{ $ticket['numero_ticket'] }} ({{ $ticket['date_ticket'] ?? '' }})</option>
              @endforeach
            </select>
            <input type="hidden" name="numero_ticket" id="numero_ticket_hidden" />
            @if(count($tickets ?? []) === 0)
              <small class="text-danger">Aucun ticket disponible pour ce vehicule et cet agent.</small>
            @else
              <small class="text-muted">{{ count($tickets) }} ticket(s) disponible(s)</small>
            @endif
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success" {{ count($tickets ?? []) === 0 ? 'disabled' : '' }}>
              <i class="bx bx-link"></i> Associer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var ticketSelect = document.getElementById('id_ticket_select');
  var numeroTicketHidden = document.getElementById('numero_ticket_hidden');

  if (ticketSelect) {
    ticketSelect.addEventListener('change', function() {
      var selectedOption = this.options[this.selectedIndex];
      numeroTicketHidden.value = selectedOption.dataset.numero || '';
    });
  }
});
</script>
@endif

<style>
@media print {
  .content-wrapper > .container-xxl > .d-flex:first-child {
    display: none !important;
  }
  .layout-menu,
  .layout-navbar,
  .content-footer,
  .btn {
    display: none !important;
  }
  .content-wrapper {
    margin: 0 !important;
    padding: 0 !important;
  }
  #fiche-sortie {
    border: none !important;
    box-shadow: none !important;
  }
}
</style>
@endsection
