@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Détails de la fiche de sortie</h4>
      <div class="d-flex gap-2">
        @if(!$fiche->id_ticket)
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAssocierTicket">
            <i class="bx bx-link me-1"></i> Associer à un Ticket
          </button>
        @endif
        <a href="{{ route('fiches_sortie.index') }}" class="btn btn-outline-secondary">
          <i class="bx bx-arrow-back me-1"></i> Retour à la liste
        </a>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-car me-2"></i>Informations véhicule</h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless">
              <tr>
                <th width="40%">Matricule</th>
                <td><span class="badge bg-primary fs-6">{{ $fiche->matricule_vehicule }}</span></td>
              </tr>
              <tr>
                <th>ID Véhicule</th>
                <td>{{ $fiche->vehicule_id }}</td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card mb-4">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-map me-2"></i>Pont de pesage</h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless">
              <tr>
                <th width="40%">Nom du pont</th>
                <td>{{ $fiche->nom_pont }}</td>
              </tr>
              <tr>
                <th>Code pont</th>
                <td><code>{{ $fiche->code_pont }}</code></td>
              </tr>
              <tr>
                <th>ID Pont</th>
                <td>{{ $fiche->id_pont }}</td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card mb-4">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-user me-2"></i>Agent</h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless">
              <tr>
                <th width="40%">Nom agent</th>
                <td>{{ $fiche->nom_agent }}</td>
              </tr>
              <tr>
                <th>Numéro agent</th>
                <td><code>{{ $fiche->numero_agent }}</code></td>
              </tr>
              <tr>
                <th>ID Agent</th>
                <td>{{ $fiche->id_agent }}</td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card mb-4">
          <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Chargement</h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless">
              <tr>
                <th width="40%">Date de chargement</th>
                <td><strong>{{ $fiche->date_chargement->format('d/m/Y') }}</strong></td>
              </tr>
              <tr>
                <th>Poids pont (kg)</th>
                <td>{{ number_format((float)$fiche->poids_pont, 0, ',', ' ') }} kg</td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    @if($fiche->id_ticket || $fiche->numero_ticket)
    <div class="card mb-4">
      <div class="card-header bg-secondary text-white">
        <h5 class="mb-0 text-white"><i class="bx bx-receipt me-2"></i>Ticket associé</h5>
      </div>
      <div class="card-body">
        <table class="table table-borderless">
          <tr>
            <th width="20%">ID Ticket</th>
            <td>{{ $fiche->id_ticket }}</td>
          </tr>
          <tr>
            <th>Numéro ticket</th>
            <td><strong>{{ $fiche->numero_ticket }}</strong></td>
          </tr>
        </table>
      </div>
    </div>
    @endif

    @if($fiche->prix_unitaire_transport || $fiche->poids_unitaire_regime)
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-money me-2"></i>Transport</h5>
      </div>
      <div class="card-body">
        <table class="table table-borderless">
          <tr>
            <th width="20%">Prix unitaire transport</th>
            <td>{{ number_format((float)$fiche->prix_unitaire_transport, 0, ',', ' ') }} FCFA</td>
          </tr>
          <tr>
            <th>Poids unitaire régime</th>
            <td>{{ number_format((float)$fiche->poids_unitaire_regime, 2, ',', ' ') }} kg</td>
          </tr>
        </table>
      </div>
    </div>
    @endif

    <div class="card">
      <div class="card-body">
        <small class="text-muted">
          <i class="bx bx-time me-1"></i>Créée le {{ $fiche->created_at->format('d/m/Y à H:i') }}
          @if($fiche->updated_at && $fiche->updated_at != $fiche->created_at)
            | Modifiée le {{ $fiche->updated_at->format('d/m/Y à H:i') }}
          @endif
        </small>
      </div>
    </div>
  </div>
</div>

<!-- Modal Associer Ticket -->
<div class="modal fade" id="modalAssocierTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-link me-2"></i>Associer à un Ticket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('fiches_sortie.associer_ticket', ['fiche_id' => $fiche->id]) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Sélectionner un ticket</label>
            <div id="tickets_loading" class="text-center py-3" style="display: none;">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
              </div>
              <p class="mt-2 mb-0">Chargement des tickets...</p>
            </div>
            <input type="text" id="ticket_input" class="form-control" placeholder="Tapez pour rechercher un ticket..." list="tickets_list" autocomplete="off" required />
            <datalist id="tickets_list"></datalist>
            <input type="hidden" name="id_ticket" id="id_ticket_hidden" />
            <input type="hidden" name="numero_ticket" id="numero_ticket_hidden" />
          </div>
          <div class="alert alert-info">
            <i class="bx bx-info-circle me-1"></i>
            Véhicule de la fiche: <strong>{{ $fiche->matricule_vehicule }}</strong>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Associer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var ticketsMap = {};
  var ticketsLoaded = false;

  var ticketInput = document.getElementById('ticket_input');
  var idTicketHidden = document.getElementById('id_ticket_hidden');
  var numeroTicketHidden = document.getElementById('numero_ticket_hidden');
  var ticketsList = document.getElementById('tickets_list');
  var ticketsLoading = document.getElementById('tickets_loading');

  // Charger les tickets quand le modal s'ouvre
  var modal = document.getElementById('modalAssocierTicket');
  modal.addEventListener('shown.bs.modal', function() {
    if (!ticketsLoaded) {
      ticketInput.style.display = 'none';
      ticketsLoading.style.display = 'block';
      
      fetch('{{ route("api.tickets_conformes") }}')
        .then(response => response.json())
        .then(tickets => {
          ticketsList.innerHTML = '';
          ticketsMap = {};
          tickets.forEach(function(t) {
            var label = (t.numero_ticket || '') + ' - ' + (t.matricule_vehicule || '') + ' - ' + (t.agent_nom || '');
            ticketsMap[label] = t.id_ticket;
            var option = document.createElement('option');
            option.value = label;
            option.dataset.id = t.id_ticket;
            ticketsList.appendChild(option);
          });
          ticketsLoaded = true;
          ticketsLoading.style.display = 'none';
          ticketInput.style.display = 'block';
          ticketInput.focus();
        })
        .catch(function(err) {
          ticketsLoading.innerHTML = '<p class="text-danger">Erreur lors du chargement des tickets</p>';
        });
    }
  });

  if (ticketInput) {
    ticketInput.addEventListener('change', function() {
      var val = this.value;
      if (ticketsMap[val] !== undefined) {
        idTicketHidden.value = ticketsMap[val];
        numeroTicketHidden.value = val;
      } else {
        idTicketHidden.value = '';
        numeroTicketHidden.value = '';
      }
    });

    ticketInput.addEventListener('input', function() {
      var val = this.value;
      if (ticketsMap[val] !== undefined) {
        idTicketHidden.value = ticketsMap[val];
        numeroTicketHidden.value = val;
      }
    });
  }
});
</script>
@endsection
