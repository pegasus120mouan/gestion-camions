@extends('layout.main')

@section('page-styles')
<style>
  .fiche-detail-card .card-header {
    background-color: #f5f5f9;
    border-bottom: 1px solid #ebeef0;
    padding: 0.75rem 1.25rem;
  }
  .fiche-detail-card .card-header h6 {
    font-weight: 600;
    color: #566a7f;
    margin-bottom: 0;
  }
  .fiche-detail-sidebar .avatar-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-color: #f5f5f9;
    color: #8592a3;
    font-size: 3rem;
  }
</style>
@endsection

@section('content')
@php
  $fmtMoney = fn ($v) => $v ? number_format((float) $v, 0, ',', ' ') . ' FCFA' : null;
  $retourUrl = url()->previous() !== url()->current() ? url()->previous() : route('fiches_sortie.index');
  $numeroFiche = $fiche->numero_fiche ?? ('#' . $fiche->id);
@endphp
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h4 class="mb-0">Détails de la fiche</h4>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $retourUrl }}" class="btn btn-secondary">
          <i class="bx bx-arrow-back me-1"></i> Retour
        </a>
      </div>
    </div>

    <div class="row">
      {{-- Colonne gauche --}}
      <div class="col-lg-4 fiche-detail-sidebar">
        <div class="card mb-4">
          <div class="card-body text-center pt-4 pb-3">
            <div class="avatar-icon d-flex align-items-center justify-content-center mx-auto mb-3">
              <i class="bx bx-file"></i>
            </div>
            <h5 class="mb-1">{{ $numeroFiche }}</h5>
            <p class="text-muted mb-3">{{ $fiche->matricule_vehicule }}</p>
            @if($fiche->date_dechargement)
              <button type="button" class="btn btn-secondary w-100" disabled>
                Fiche déchargée
              </button>
            @else
              <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#modalDechargement{{ $fiche->id }}">
                En attente de déchargement
              </button>
            @endif
          </div>
        </div>

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Résumé</h6></div>
          <div class="card-body">
            <p class="mb-2"><strong>Date chargement :</strong> {{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '—' }}</p>
            <p class="mb-2">
              <strong>Date déchargement :</strong>
              @if($fiche->date_dechargement)
                {{ $fiche->date_dechargement->format('d/m/Y') }}
              @else
                <span class="text-muted">Pas encore déchargé</span>
              @endif
            </p>
            <p class="mb-2">
              <strong>Poids :</strong>
              @if($fiche->poids_pont)
                {{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }} kg
              @else
                —
              @endif
            </p>
            <p class="mb-0"><strong>Produit :</strong> {{ $fiche->nom_produit ?: '—' }}</p>
          </div>
        </div>

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Agent</h6></div>
          <div class="card-body">
            <p class="mb-2"><strong>Nom :</strong> {{ $fiche->nom_agent ?: '—' }}</p>
            <p class="mb-2"><strong>N° agent :</strong> {{ $fiche->numero_agent ?: '—' }}</p>
            @if($fiche->montant_agent)
              <p class="mb-0"><strong>Montant agent :</strong> {{ $fmtMoney($fiche->montant_agent) }}</p>
            @endif
          </div>
        </div>

        @if($fiche->id_ticket || $fiche->numero_ticket)
        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Ticket</h6></div>
          <div class="card-body">
            <p class="mb-2"><strong>N° ticket :</strong> {{ $fiche->numero_ticket ?: '—' }}</p>
            <p class="mb-0"><strong>ID ticket :</strong> {{ $fiche->id_ticket ?: '—' }}</p>
          </div>
        </div>
        @endif

        <div class="d-grid gap-2 mb-4">
          <a href="{{ route('fiches_sortie.pdf', ['fiche_id' => $fiche->id]) }}" target="_blank" class="btn btn-outline-secondary">
            <i class="bx bx-printer me-1"></i> Imprimer PDF
          </a>
          @if(!$fiche->id_ticket)
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAssocierTicket">
              <i class="bx bx-link me-1"></i> Associer à un ticket
            </button>
          @endif
        </div>
      </div>

      {{-- Colonne droite --}}
      <div class="col-lg-8">
        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Pont & destination</h6></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p class="mb-2"><strong>Nom du pont :</strong> {{ $fiche->nom_pont ?: '—' }}</p>
                <p class="mb-2"><strong>Code pont :</strong> {{ $fiche->code_pont ?: '—' }}</p>
              </div>
              <div class="col-md-6">
                <p class="mb-2"><strong>Usine :</strong> {{ $fiche->usine ?: '—' }}</p>
                <p class="mb-2"><strong>Parc :</strong> {{ $fiche->nom_parc ?: '—' }}</p>
                <p class="mb-0"><strong>Produit :</strong> {{ $fiche->nom_produit ?: '—' }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Véhicule</h6></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p class="mb-2"><strong>N° fiche :</strong> {{ $numeroFiche }}</p>
                <p class="mb-0"><strong>Matricule :</strong> {{ $fiche->matricule_vehicule }}</p>
              </div>
              <div class="col-md-6">
                <p class="mb-0"><strong>ID interne :</strong> #{{ $fiche->id }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Chauffeurs</h6></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p class="mb-2"><strong>N° camion :</strong> {{ $fiche->matricule_vehicule ?: '—' }}</p>
                <p class="mb-2">
                  <strong>Date d'enregistrement :</strong>
                  {{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '—' }}
                </p>
              </div>
              <div class="col-md-6">
                @if($chauffeur ?? null)
                  <p class="mb-2"><strong>Chauffeur :</strong> {{ $chauffeur->nom }} {{ $chauffeur->prenoms }}</p>
                  <p class="mb-2"><strong>Contact :</strong> {{ $chauffeur->contact ?: '—' }}</p>
                  <p class="mb-0"><strong>Groupe :</strong> {{ $chauffeur->groupe?->nom_groupe ?? '—' }}</p>
                @else
                  <p class="mb-0 text-muted">
                    Aucun chauffeur trouvé pour le camion <strong>{{ $fiche->matricule_vehicule }}</strong>
                    à la date du {{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '—' }}.
                  </p>
                @endif
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Chargement / Déchargement</h6></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p class="mb-2"><strong>Date de chargement :</strong> {{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '—' }}</p>
                <p class="mb-2">
                  <strong>Date de déchargement :</strong>
                  @if($fiche->date_dechargement)
                    {{ $fiche->date_dechargement->format('d/m/Y') }}
                  @else
                    <span class="text-muted">Pas encore déchargé</span>
                  @endif
                </p>
              </div>
              <div class="col-md-6">
                <p class="mb-2">
                  <strong>Poids pont (kg) :</strong>
                  @if($fiche->poids_pont)
                    {{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }}
                  @else
                    —
                  @endif
                </p>
                <p class="mb-0"><strong>N° ticket :</strong> {{ $fiche->numero_ticket ?: '—' }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Frais & montants camion</h6></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p class="mb-2"><strong>Carburant :</strong> {{ $fmtMoney($fiche->carburant) ?? '—' }}</p>
                <p class="mb-2"><strong>Frais de route :</strong> {{ $fmtMoney($fiche->frais_route) ?? '—' }}</p>
              </div>
              <div class="col-md-6">
                <p class="mb-2">
                  <strong>Prix unitaire camion :</strong>
                  @if($fiche->prix_unitaire_camion)
                    {{ number_format((float) $fiche->prix_unitaire_camion, 0, ',', ' ') }} FCFA/kg
                  @else
                    —
                  @endif
                </p>
                <p class="mb-0"><strong>Montant camion :</strong> {{ $fmtMoney($fiche->montant_camion) ?? '—' }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Chef des chargeurs</h6></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p class="mb-2">
                  <strong>Nom :</strong>
                  @if($fiche->id_chef_chargeur && isset($chefChargeur))
                    {{ $chefChargeur->nom }} {{ $chefChargeur->prenoms }}
                  @else
                    Non assigné
                  @endif
                </p>
                @if($fiche->id_chef_chargeur && isset($chefChargeur) && $chefChargeur->contact)
                  <p class="mb-0"><strong>Contact :</strong> {{ $chefChargeur->contact }}</p>
                @endif
              </div>
              <div class="col-md-6">
                <p class="mb-0">
                  <strong>Paiement chargeur :</strong>
                  @if($paiementChargeur)
                    {{ number_format($paiementChargeur, 0, ',', ' ') }} FCFA
                    @if(isset($prixUnitaireChargeur))
                      <br><small class="text-muted">({{ number_format($prixUnitaireChargeur, 0, ',', ' ') }} × {{ number_format((float) $fiche->poids_pont / 1000, 2, ',', ' ') }} T)</small>
                    @endif
                  @else
                    —
                  @endif
                </p>
              </div>
            </div>
          </div>
        </div>

        @if($fiche->prix_unitaire_transport || $fiche->poids_unitaire_regime || $fiche->montant_paye_transporteur)
        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Transport</h6></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p class="mb-2"><strong>Prix unitaire transport :</strong> {{ $fmtMoney($fiche->prix_unitaire_transport) ?? '—' }}</p>
                <p class="mb-0">
                  <strong>Poids unitaire régime :</strong>
                  @if($fiche->poids_unitaire_regime)
                    {{ number_format((float) $fiche->poids_unitaire_regime, 2, ',', ' ') }} kg
                  @else
                    —
                  @endif
                </p>
              </div>
              <div class="col-md-6">
                <p class="mb-0"><strong>Montant payé transporteur :</strong> {{ $fmtMoney($fiche->montant_paye_transporteur) ?? '—' }}</p>
              </div>
            </div>
          </div>
        </div>
        @endif

        <div class="card mb-4 fiche-detail-card">
          <div class="card-header"><h6>Informations complémentaires</h6></div>
          <div class="card-body">
            <p class="mb-2">
              <strong>Créée le :</strong> {{ $fiche->created_at->format('d/m/Y à H:i') }}
            </p>
            @if($fiche->updated_at && $fiche->updated_at != $fiche->created_at)
              <p class="mb-0">
                <strong>Modifiée le :</strong> {{ $fiche->updated_at->format('d/m/Y à H:i') }}
              </p>
            @else
              <p class="mb-0 text-muted">Aucune modification enregistrée</p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@if(!$fiche->date_dechargement)
  @include('fiches_sortie._dechargement_modal', [
    'fiche' => $fiche,
    'gerableParPont' => $gerableParPont ?? [],
    'parcsParPont' => $parcsParPont ?? collect(),
  ])
@endif

<div class="modal fade" id="modalAssocierTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bx bx-link me-2"></i>Associer à un ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('fiches_sortie.associer_ticket', ['fiche_id' => $fiche->id]) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Sélectionner un ticket</label>
            <div id="tickets_loading" class="text-center py-3" style="display: none;">
              <div class="spinner-border text-secondary" role="status">
                <span class="visually-hidden">Chargement...</span>
              </div>
              <p class="mt-2 mb-0">Chargement des tickets...</p>
            </div>
            <input type="text" id="ticket_input" class="form-control" placeholder="Tapez pour rechercher un ticket..." list="tickets_list" autocomplete="off" required />
            <datalist id="tickets_list"></datalist>
            <input type="hidden" name="id_ticket" id="id_ticket_hidden" />
            <input type="hidden" name="numero_ticket" id="numero_ticket_hidden" />
          </div>
          <div class="alert alert-light border mb-0">
            <i class="bx bx-info-circle me-1"></i>
            Véhicule de la fiche : <strong>{{ $fiche->matricule_vehicule }}</strong>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Associer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function calculerMontantCamion(ficheId) {
  var poidsEl = document.getElementById('poids_pont_' + ficheId);
  var prixEl = document.getElementById('prix_unitaire_camion_' + ficheId);
  var montantEl = document.getElementById('montant_camion_' + ficheId);
  var displayEl = document.getElementById('montant_camion_display_' + ficheId);
  if (!poidsEl || !prixEl || !montantEl || !displayEl) return;

  var montant = (parseFloat(poidsEl.value) || 0) * (parseFloat(prixEl.value) || 0);
  montantEl.value = montant;
  displayEl.value = montant.toLocaleString('fr-FR').replace(/,/g, ' ');
}

document.addEventListener('DOMContentLoaded', function() {
  @if(!$fiche->date_dechargement)
  calculerMontantCamion({{ $fiche->id }});
  @endif

  @if(session('open_dechargement_modal') == $fiche->id)
  var dechargementModal = document.getElementById('modalDechargement{{ $fiche->id }}');
  if (dechargementModal && window.bootstrap) {
    bootstrap.Modal.getOrCreateInstance(dechargementModal).show();
  }
  @endif

  var ticketsMap = {};
  var ticketsLoaded = false;
  var ticketInput = document.getElementById('ticket_input');
  var idTicketHidden = document.getElementById('id_ticket_hidden');
  var numeroTicketHidden = document.getElementById('numero_ticket_hidden');
  var ticketsList = document.getElementById('tickets_list');
  var ticketsLoading = document.getElementById('tickets_loading');
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
        .catch(function() {
          ticketsLoading.innerHTML = '<p class="text-danger">Erreur lors du chargement des tickets</p>';
        });
    }
  });

  function syncTicketHidden(val) {
    if (ticketsMap[val] !== undefined) {
      idTicketHidden.value = ticketsMap[val];
      numeroTicketHidden.value = val;
    } else {
      idTicketHidden.value = '';
      numeroTicketHidden.value = '';
    }
  }

  if (ticketInput) {
    ticketInput.addEventListener('change', function() { syncTicketHidden(this.value); });
    ticketInput.addEventListener('input', function() { syncTicketHidden(this.value); });
  }
});
</script>
@endsection
