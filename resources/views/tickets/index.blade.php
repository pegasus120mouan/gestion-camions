@extends('layout.main')
@section('title', 'Tickets')

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
  }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Mes Tickets</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddTicket">
        <i class="bx bx-plus me-1"></i>Ajouter un ticket
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('tickets.index') }}" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Vehicule</label>
            <input type="text" name="vehicule" id="vehicule_input" class="form-control" placeholder="Matricule..." value="{{ request('vehicule') }}" list="vehicules_list" autocomplete="off" />
            <datalist id="vehicules_list">
              @foreach($vehicules ?? [] as $matricule)
                <option value="{{ $matricule }}">
              @endforeach
            </datalist>
          </div>
          <div class="col-md-3">
            <label class="form-label">Usine</label>
            <input type="text" name="usine" class="form-control" placeholder="Nom usine..." value="{{ request('usine') }}" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Agent</label>
            <input type="text" name="agent" class="form-control" placeholder="Nom agent..." value="{{ request('agent') }}" />
          </div>
          <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Reinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Date ticket</th>
              <th>N°Ticket</th>
              <th>Usine</th>
              <th>Agent</th>
              <th>Vehicule</th>
              <th>Poids Usine</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($tickets as $t)
              <tr>
                <td>
                  @php
                    $dateTicket = $t['date_ticket'] ?? '';
                    if ($dateTicket) {
                      try {
                        $dateTicket = \Carbon\Carbon::parse($dateTicket)->format('d-m-Y');
                      } catch (\Exception $e) {}
                    }
                  @endphp
                  {{ $dateTicket }}
                </td>
                <td>
                  <a href="#" class="text-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTicketDetail{{ $loop->index }}">
                    {{ $t['numero_ticket'] ?? '' }}
                  </a>
                </td>
                <td>{{ $t['nom_usine'] ?? '-' }}</td>
                <td>{{ $t['nom_agent'] ?? '-' }}</td>
                <td>
                  @if(!empty($t['vehicule_id']))
                    <a href="{{ route('vehicules.depenses', ['vehicule_id' => $t['vehicule_id'], 'matricule' => $t['matricule_vehicule'] ?? '']) }}">
                      {{ $t['matricule_vehicule'] ?? '' }}
                    </a>
                  @else
                    {{ $t['matricule_vehicule'] ?? '-' }}
                  @endif
                </td>
                <td>{{ number_format((float)($t['poids'] ?? 0), 0, ',', ' ') }}</td>
                <td>
                  <a href="{{ route('tickets.pdf', ['id' => $t['id_ticket']]) }}" class="btn btn-sm btn-outline-secondary me-1" target="_blank" rel="noopener" title="Imprimer en PDF">
                    <i class="bx bx-printer"></i>
                  </a>
                  @php
                    $ticketValide = in_array($t['conformite'] ?? '', ['valide', 'conforme'], true);
                    $estCamionPgf = (bool) ($t['est_camion_pgf'] ?? false);
                  @endphp
                  <button
                    type="button"
                    class="btn btn-sm {{ $ticketValide ? 'btn-secondary' : 'btn-outline-success' }}"
                    title="{{ $ticketValide ? 'Ticket déjà validé' : ($estCamionPgf ? 'Valider avec fiche de sortie' : 'Valider le ticket') }}"
                    data-bs-toggle="modal"
                    data-bs-target="#modalValiderTicket{{ $loop->index }}"
                    @if($ticketValide) disabled @endif
                  >
                    <i class="bx bx-check"></i> Valider
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center">Aucun ticket</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @php
      $ticketsArray = is_array($tickets) ? $tickets : [];
      $ticketCount = count($ticketsArray);
    @endphp

    
    @if(isset($pagination) && is_array($pagination) && $pagination['last_page'] > 1)
      <nav class="mt-4">
        <ul class="pagination justify-content-center">
          @php
            $currentPage = (int)($pagination['current_page'] ?? 1);
            $lastPage = (int)($pagination['last_page'] ?? 1);
            $total = (int)($pagination['total'] ?? 0);
          @endphp

          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route('tickets.index', array_merge(request()->only(['vehicule', 'usine', 'agent']), ['page' => $currentPage - 1])) }}">Precedent</a>
          </li>

          @for($i = 1; $i <= $lastPage; $i++)
            @if($i == 1 || $i == $lastPage || abs($i - $currentPage) <= 2)
              <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route('tickets.index', array_merge(request()->only(['vehicule', 'usine', 'agent']), ['page' => $i])) }}">{{ $i }}</a>
              </li>
            @elseif($i == 2 && $currentPage > 4)
              <li class="page-item disabled"><span class="page-link">...</span></li>
            @elseif($i == $lastPage - 1 && $currentPage < $lastPage - 3)
              <li class="page-item disabled"><span class="page-link">...</span></li>
            @endif
          @endfor

          <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route('tickets.index', array_merge(request()->only(['vehicule', 'usine', 'agent']), ['page' => $currentPage + 1])) }}">Suivant</a>
          </li>
        </ul>
        <p class="text-center text-muted">Page {{ $currentPage }} sur {{ $lastPage }} ({{ $total }} tickets)</p>
      </nav>
    @endif
  </div>
</div>

<!-- Modals pour afficher les détails du ticket -->
@foreach($tickets as $index => $t)
  @php
    $dateTicketModal = $t['date_ticket'] ?? '';
    if ($dateTicketModal) {
      try {
        $dateTicketModal = \Carbon\Carbon::parse($dateTicketModal)->format('d-m-Y');
      } catch (\Exception $e) {}
    }
    $poidsUsineModal = (float)($t['poids'] ?? 0);
    $poidsParc = (float)($t['poids_parc'] ?? 0);
    $prixTransportModal = (float)($t['prix_unitaire_transport'] ?? 0);
    $poidsRegimeModal = (float)($t['poids_unitaire_regime'] ?? 0);
    $montantTransportModal = $prixTransportModal > 0 ? $poidsUsineModal * $prixTransportModal : null;
    $montantRegimeModal = $poidsRegimeModal > 0 ? $poidsUsineModal * $poidsRegimeModal : null;
    $poidsEcartModal = $poidsParc > 0 ? $poidsUsineModal - $poidsParc : null;
  @endphp
  <div class="modal fade" id="modalTicketDetail{{ $index }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="bx bx-receipt me-2"></i>Ticket {{ $t['numero_ticket'] ?? '' }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="card bg-light border-0 h-100">
                <div class="card-body">
                  <h6 class="card-title text-primary mb-3"><i class="bx bx-info-circle me-1"></i>Informations générales</h6>
                  <div class="mb-2"><strong>N° Ticket:</strong> {{ $t['numero_ticket'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Véhicule:</strong> {{ $t['matricule_vehicule'] ?? '-' }}</div>
                  @php
                    $matriculeVehicule = $t['matricule_vehicule'] ?? '';
                    $transporteurVehicule = \App\Models\TransporteurVehicule::with('transporteur')
                        ->where('matricule_vehicule', $matriculeVehicule)
                        ->first();
                    $transporteurNom = 'Non renseigné';
                    if ($transporteurVehicule?->transporteur) {
                        $transporteurNom = $transporteurVehicule->transporteur->code . ' — '
                            . $transporteurVehicule->transporteur->nom . ' ' . $transporteurVehicule->transporteur->prenoms;
                    } else {
                        $codeTransporteurVehicule = \App\Models\CodeTransporteurVehicule::with('codeTransporteur')
                            ->where('matricule_vehicule', $matriculeVehicule)
                            ->first();
                        if ($codeTransporteurVehicule && $codeTransporteurVehicule->codeTransporteur) {
                            $transporteurNom = $codeTransporteurVehicule->codeTransporteur->nom;
                        }
                    }
                  @endphp
                  <div class="mb-2"><strong>Transporteur:</strong> <span class="badge bg-info">{{ $transporteurNom }}</span></div>
                  <div class="mb-2"><strong>Usine:</strong> {{ $t['nom_usine'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Produit:</strong> {{ $t['nom_produit'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Groupe:</strong> {{ $t['nom_groupe'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Agent:</strong> {{ $t['nom_agent'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Origine:</strong> {{ $t['origine'] ?? '-' }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-light border-0 h-100">
                <div class="card-body">
                  <h6 class="card-title text-primary mb-3"><i class="bx bx-calendar me-1"></i>Dates</h6>
                  <div class="mb-2"><strong>Date chargement:</strong> {{ $t['date_chargement_fiche'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Date déchargement:</strong> {{ $dateTicketModal ?: '-' }}</div>
                  @php
                    $dateAjoutModal = $t['created_at'] ?? '';
                    if ($dateAjoutModal) {
                      try {
                        $dateAjoutModal = \Carbon\Carbon::parse($dateAjoutModal)->format('d-m-Y H:i');
                      } catch (\Exception $e) {}
                    }
                  @endphp
                  <div class="mb-2"><strong>Date d'ajout:</strong> {{ $dateAjoutModal ?: '-' }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-light border-0 h-100">
                <div class="card-body">
                  <h6 class="card-title text-success mb-3"><i class="bx bx-package me-1"></i>Poids</h6>
                  <div class="mb-2"><strong>Poids sur Parc:</strong> {{ $poidsParc > 0 ? number_format($poidsParc, 0, ',', ' ') . ' kg' : '-' }}</div>
                  <div class="mb-2"><strong>Poids Usine:</strong> {{ number_format($poidsUsineModal, 0, ',', ' ') }} kg</div>
                  <div class="mb-2"><strong>Poids Ecart:</strong> 
                    @if($poidsEcartModal !== null)
                      <span class="{{ $poidsEcartModal < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($poidsEcartModal, 0, ',', ' ') }} kg</span>
                    @else
                      -
                    @endif
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-light border-0 h-100">
                <div class="card-body">
                  <h6 class="card-title text-warning mb-3"><i class="bx bx-money me-1"></i>Montants</h6>
                  <div class="mb-2"><strong>Prix unitaire:</strong>
                    @if(($t['prix_unitaire_agent'] ?? null) !== null)
                      {{ number_format((float) $t['prix_unitaire_agent'], 0, ',', ' ') }} FCFA
                    @else
                      -
                    @endif
                  </div>
                  <div class="mb-2"><strong>Montant calculé:</strong>
                    @if(($t['montant_calcule'] ?? null) !== null)
                      {{ number_format((float) $t['montant_calcule'], 0, ',', ' ') }} FCFA
                    @else
                      -
                    @endif
                  </div>
                  <div class="mb-2"><strong>Montant payé:</strong> {{ number_format((float)($t['montant_paie'] ?? 0), 0, ',', ' ') }} FCFA</div>
                  <div class="mb-2"><strong>Prix unitaire Agent:</strong>
                    @if(($t['prix_unitaire_agent'] ?? null) !== null)
                      {{ number_format((float) $t['prix_unitaire_agent'], 0, ',', ' ') }} FCFA
                    @else
                      -
                    @endif
                  </div>
                  <div class="mb-2"><strong>Montant Agents:</strong>
                    @if(($t['montant_calcule'] ?? null) !== null)
                      {{ number_format((float) $t['montant_calcule'], 0, ',', ' ') }} FCFA
                    @else
                      -
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <form method="POST" action="{{ route('tickets.confirm_unipalm', ['id' => $t['id_ticket']]) }}" class="d-inline me-2">
            @csrf
            <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Vérifier avec Unipalm</button>
          </form>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>
@endforeach

<!-- Modals pour modifier le Prix Unitaire Transport -->
@foreach($tickets as $t)
  @if($t['fiche_id'])
  <div class="modal fade" id="modalPrixTransport{{ $t['id_ticket'] }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Modifier les valeurs</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="{{ route('fiches_sortie.update_prix_transport', ['fiche_id' => $t['fiche_id']]) }}">
          @csrf
          <div class="modal-body">
            <p class="text-muted small mb-2">Ticket: {{ $t['numero_ticket'] ?? '' }}</p>
            <div class="mb-3">
              <label class="form-label">Prix Unitaire Transport</label>
              <input type="number" name="prix_unitaire_transport" class="form-control" value="{{ $t['prix_unitaire_transport'] ?? '' }}" step="0.01" min="0" />
            </div>
            <div class="mb-3">
              <label class="form-label">Poids Unitaire Régime</label>
              <input type="number" name="poids_unitaire_regime" class="form-control" value="{{ $t['poids_unitaire_regime'] ?? '' }}" step="0.01" min="0" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif
@endforeach

<!-- Modals Confirmer avec Unipalm -->
@foreach($tickets as $t)
<div class="modal fade" id="modalConfirmUnipalm{{ $loop->index }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-check me-2"></i>Confirmer avec Unipalm</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Voulez-vous vérifier ce ticket dans l'API Unipalm ?</p>
        <div class="alert alert-info mb-0">
          <strong>Date:</strong> {{ isset($t['date_ticket']) ? \Carbon\Carbon::parse($t['date_ticket'])->format('d-m-Y') : '-' }}<br>
          <strong>N° Ticket:</strong> {{ $t['numero_ticket'] ?? '' }}<br>
          <strong>Usine:</strong> {{ $t['nom_usine'] ?? '-' }}<br>
          <strong>Groupe:</strong> {{ $t['nom_groupe'] ?? '-' }}<br>
          <strong>Agent:</strong> {{ $t['nom_agent'] ?? '-' }}<br>
          <strong>Poids:</strong> {{ number_format((float)($t['poids'] ?? 0), 0, ',', ' ') }} kg
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form method="POST" action="{{ route('tickets.confirm_unipalm', ['id' => $t['id_ticket']]) }}" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Vérifier avec Unipalm</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

<!-- Modals validation ticket + fiche de sortie -->
@foreach($tickets as $index => $t)
  @php
    $ticketValideModal = in_array($t['conformite'] ?? '', ['valide', 'conforme'], true);
    $estCamionPgf = (bool) ($t['est_camion_pgf'] ?? false);
    $matriculeTicket = trim((string) ($t['matricule_vehicule'] ?? ''));
    $idAgentTicket = (int) ($t['id_agent'] ?? 0);
    $fichesPourTicket = collect();
    if ($estCamionPgf) {
        $fichesPourTicket = ($fichesNonDechargees ?? collect())->filter(function ($fiche) use ($matriculeTicket, $idAgentTicket) {
            $matchVehicule = $matriculeTicket !== ''
                && strcasecmp(trim((string) $fiche->matricule_vehicule), $matriculeTicket) === 0;
            $matchAgent = $idAgentTicket > 0 && (int) $fiche->id_agent === $idAgentTicket;

            return $matchVehicule || $matchAgent;
        });
        if ($fichesPourTicket->isEmpty()) {
            $fichesPourTicket = $fichesNonDechargees ?? collect();
        }
    }
  @endphp
  @if(!$ticketValideModal)
  <div class="modal fade" id="modalValiderTicket{{ $index }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog {{ $estCamionPgf ? 'modal-xl modal-dialog-scrollable' : '' }}">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title text-white">
            <i class="bx bx-check-circle me-2"></i>Valider le ticket — {{ $t['numero_ticket'] ?? '' }}
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('tickets.valider', $t['id_ticket']) }}">
          @csrf
          <div class="modal-body">
            <div class="alert alert-light border mb-3">
              <div class="row g-2">
                <div class="col-md-3"><strong>Ticket :</strong> {{ $t['numero_ticket'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Véhicule :</strong> {{ $t['matricule_vehicule'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Agent :</strong> {{ $t['nom_agent'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Usine :</strong> {{ $t['nom_usine'] ?? '-' }}</div>
              </div>
            </div>

            @if($estCamionPgf)
              <p class="text-muted small mb-3">
                <i class="bx bx-info-circle me-1"></i>Camion du groupe PGF — associez une fiche de sortie non déchargée.
              </p>
              <h6 class="mb-3">Fiches de sortie non déchargées</h6>

              @if($fichesPourTicket->isEmpty())
                <div class="alert alert-warning mb-0">
                  Aucune fiche de sortie en attente de déchargement pour votre équipe.
                </div>
              @else
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead>
                      <tr>
                        <th style="width: 40px;"></th>
                        <th>N° Fiche</th>
                        <th>Date chargement</th>
                        <th>Véhicule</th>
                        <th>Agent</th>
                        <th>Pont</th>
                        <th>Usine</th>
                        <th>Poids parc</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($fichesPourTicket as $fiche)
                        <tr>
                          <td>
                            <input
                              type="radio"
                              name="fiche_id"
                              value="{{ $fiche->id }}"
                              class="form-check-input"
                              required
                              {{ $loop->first ? 'checked' : '' }}
                            />
                          </td>
                          <td>{{ $fiche->numero_fiche ?? ('#' . $fiche->id) }}</td>
                          <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d-m-Y') : '-' }}</td>
                          <td>{{ $fiche->matricule_vehicule ?? '-' }}</td>
                          <td>{{ $fiche->nom_agent ?? '-' }}</td>
                          <td>{{ $fiche->nom_pont ?? '-' }}</td>
                          <td>{{ $fiche->usine ?? '-' }}</td>
                          <td>{{ $fiche->poids_pont ? number_format((float) $fiche->poids_pont, 0, ',', ' ') . ' kg' : '-' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            @else
              <div class="alert alert-info mb-0">
                <i class="bx bx-check-circle me-1"></i>
                Ce camion n'appartient pas au groupe PGF. Le ticket sera validé directement, sans fiche de sortie.
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success" @if($estCamionPgf && $fichesPourTicket->isEmpty()) disabled @endif>
              <i class="bx bx-check me-1"></i>Valider
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif
@endforeach

<!-- Modal Ajouter Ticket -->
<div class="modal fade" id="modalAddTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-plus me-2"></i>Ajouter un ticket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('tickets.store') }}">
        @csrf
        <div class="modal-body">
          {{-- Ligne 1 : Date Ticket + N° Ticket --}}
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date Ticket <span class="text-danger">*</span></label>
              <input type="date" name="date_ticket" class="form-control" required value="" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">N° Ticket <span class="text-danger">*</span></label>
              <input type="text" name="numero_ticket" class="form-control" required placeholder="Ex: TKT-001" />
            </div>
          </div>
          {{-- Ligne 2 : Véhicule + Poids --}}
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Véhicule <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="text" id="ticket_vehicule_search" class="form-control" placeholder="Tapez le matricule..." autocomplete="off" required value="{{ old('matricule_vehicule') }}" />
                <div id="ticket_vehicule_dropdown" class="dropdown-menu w-100 shadow-sm" style="max-height: 250px; overflow-y: auto; display: none;"></div>
              </div>
              <input type="hidden" name="matricule_vehicule" id="ticket_matricule_vehicule" value="{{ old('matricule_vehicule') }}" />
              <input type="hidden" name="vehicule_id" id="ticket_vehicule_id" value="{{ old('vehicule_id') }}" />
              <small class="text-muted">Saisissez quelques caractères puis choisissez un matricule dans la liste.</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Poids (kg)</label>
              <input type="number" name="poids" class="form-control" step="0.01" min="0" placeholder="Ex: 15000" value="{{ old('poids') }}" />
            </div>
          </div>
          {{-- Ligne 3 : Pont --}}
          <div class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label">Pont</label>
              <select name="id_pont" id="ticket_id_pont" class="form-select">
                <option value="" data-gerable="0">-- Aucun pont --</option>
                @foreach($tousLesPonts ?? [] as $pont)
                  <option value="{{ $pont['id_pont'] }}" data-gerable="{{ $pont['gerable'] ? '1' : '0' }}" data-starred="{{ $pont['gerable'] ? '1' : '0' }}" @selected(old('id_pont') == ($pont['id_pont'] ?? null))>
                    {{ $pont['nom_pont'] ?? '' }} ({{ $pont['code_pont'] ?? '' }}){{ $pont['gerable'] ? ' ★' : '' }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          {{-- Ligne 4 : Produit + Parc (parc conditionnel si pont gérable) --}}
          <div class="row">
            <div id="col_produit" class="col-md-6 mb-3">
              <label class="form-label">Produit <span class="text-danger d-none" id="ticket_produit_required">*</span></label>
              <select name="produit_id" id="ticket_produit_id" class="form-select">
                <option value="">-- Sélectionner un produit --</option>
                @foreach($produitsLocaux ?? [] as $produit)
                  <option value="{{ $produit->id }}" @selected(old('produit_id') == $produit->id)>{{ $produit->nom }}</option>
                @endforeach
              </select>
            </div>
            <div id="col_parc" class="col-md-6 mb-3" style="display:none;">
              <label class="form-label" id="ticket_parc_label">Parc <span class="text-danger d-none" id="ticket_parc_required">*</span></label>
              <select name="parc_id" id="ticket_parc_id" class="form-select" disabled>
                <option value="">-- Sélectionner d'abord un produit --</option>
              </select>
              <div class="invalid-feedback">Le parc est obligatoire pour un pont gérable.</div>
            </div>
          </div>
          {{-- Ligne 5 : Usine (filtrée par produit) --}}
          <div class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label">Usine <span class="text-danger">*</span></label>
              <select name="id_usine" id="ticket_id_usine" class="form-select" required>
                <option value="">-- Sélectionner d'abord un produit --</option>
              </select>
            </div>
          </div>
          {{-- Ligne 6 : Groupe + Agent --}}
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Groupe <span class="text-danger">*</span></label>
              <select name="particulier_groupe_id" id="ticket_particulier_groupe_id" class="form-select" required>
                <option value="">-- Sélectionner un groupe --</option>
                @foreach($groupesParticuliers ?? [] as $groupeItem)
                  <option value="{{ $groupeItem->id }}" @selected(old('particulier_groupe_id') == $groupeItem->id)>{{ $groupeItem->nom_groupe }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Agent <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="text" id="ticket_agent_search" class="form-control" placeholder="Tapez numéro ou nom d'agent..." autocomplete="off" disabled value="" />
                <div id="ticket_agent_dropdown" class="dropdown-menu w-100 shadow-sm" style="max-height: 250px; overflow-y: auto; display: none;"></div>
              </div>
              <input type="hidden" name="agent_ref" id="ticket_agent_ref" value="{{ old('agent_ref') }}" />
              <small class="text-muted">Saisissez quelques caractères puis choisissez un agent dans la liste.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" id="btnSubmitTicket" disabled>
            <i class="bx bx-check me-1"></i>Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var agentsParGroupe = @json($agentsParGroupe ?? []);
var agentsTicketCourants = [];
var oldParticulierGroupeId = @json(old('particulier_groupe_id'));
var oldAgentRef = @json(old('agent_ref'));
var oldMatriculeVehicule = @json(old('matricule_vehicule'));
var oldVehiculeId = @json(old('vehicule_id'));
var oldPontId = @json(old('id_pont'));
var oldProduitId = @json(old('produit_id'));
var oldParcId = @json(old('parc_id'));
var pontGerableParId = @json(
  collect($tousLesPonts ?? [])->mapWithKeys(function ($p) {
    return [(string) ($p['id_pont'] ?? 0) => (bool) ($p['gerable'] ?? false)];
  })->all()
);
var vehiculesTicket = @json(
  collect($vehiculesApi ?? [])->map(function ($v) {
    return [
      'matricule' => $v['matricule_vehicule'] ?? '',
      'id' => $v['vehicules_id'] ?? '',
    ];
  })->filter(fn ($v) => ($v['matricule'] ?? '') !== '')->values()
);
var vehiculesTicketMap = {};
vehiculesTicket.forEach(function(v) {
  vehiculesTicketMap[v.matricule] = v.id;
});

function syncVehiculeTicketHiddenFields() {
  var matricule = $('#ticket_vehicule_search').val().trim();
  var vehiculeId = vehiculesTicketMap[matricule] || '';
  $('#ticket_matricule_vehicule').val(matricule);
  $('#ticket_vehicule_id').val(vehiculeId);
}

function selectVehiculeTicket(matricule, vehiculeId) {
  $('#ticket_vehicule_search').val(matricule);
  $('#ticket_matricule_vehicule').val(matricule);
  $('#ticket_vehicule_id').val(vehiculeId);
  $('#ticket_vehicule_dropdown').hide();
  syncTicketSubmitButton();
}

function renderVehiculesTicketDropdown() {
  var search = $('#ticket_vehicule_search').val().trim().toLowerCase();
  var $dropdown = $('#ticket_vehicule_dropdown');
  $dropdown.empty();

  if (!search) {
    $dropdown.hide();
    syncVehiculeTicketHiddenFields();
    return;
  }

  var matches = vehiculesTicket.filter(function(v) {
    return v.matricule.toLowerCase().indexOf(search) !== -1;
  }).slice(0, 50);

  if (matches.length === 0) {
    $dropdown.append('<span class="dropdown-item text-muted disabled">Aucun véhicule trouvé</span>');
  } else {
    matches.forEach(function(v) {
      $dropdown.append(
        '<a href="#" class="dropdown-item ticket-vehicule-option" data-matricule="' +
        $('<div>').text(v.matricule).html() +
        '" data-vehicule-id="' + v.id + '">' +
        $('<div>').text(v.matricule).html() +
        '</a>'
      );
    });
  }

  $dropdown.show();
  syncVehiculeTicketHiddenFields();
}

function initVehiculeTicketAutocomplete() {
  if (oldMatriculeVehicule) {
    $('#ticket_vehicule_search').val(oldMatriculeVehicule);
    $('#ticket_matricule_vehicule').val(oldMatriculeVehicule);
    if (oldVehiculeId) {
      $('#ticket_vehicule_id').val(oldVehiculeId);
    } else if (vehiculesTicketMap[oldMatriculeVehicule]) {
      $('#ticket_vehicule_id').val(vehiculesTicketMap[oldMatriculeVehicule]);
    }
  }
}

function agentLabelParRef(agentRef) {
  if (!agentRef) return '';
  for (var i = 0; i < agentsTicketCourants.length; i++) {
    if (String(agentsTicketCourants[i].id) === String(agentRef)) {
      return agentsTicketCourants[i].label;
    }
  }
  return '';
}

function resetAgentTicketSelection() {
  $('#ticket_agent_ref').val('');
  $('#ticket_agent_search').val('');
}

function selectAgentTicket(agentRef, label) {
  $('#ticket_agent_ref').val(agentRef);
  $('#ticket_agent_search').val(label);
  $('#ticket_agent_dropdown').hide();
  syncTicketSubmitButton();
}

function renderAgentsTicketDropdown() {
  var search = $('#ticket_agent_search').val().trim().toLowerCase();
  var $dropdown = $('#ticket_agent_dropdown');
  $dropdown.empty();

  if (!$('#ticket_agent_search').prop('disabled') && !search) {
    $dropdown.hide();
    return;
  }

  if ($('#ticket_agent_search').prop('disabled')) {
    $dropdown.hide();
    return;
  }

  var matches = agentsTicketCourants.filter(function(agent) {
    if (!search) return true;
    return agent.label.toLowerCase().indexOf(search) !== -1;
  }).slice(0, 50);

  if (matches.length === 0) {
    $dropdown.append('<span class="dropdown-item text-muted disabled">Aucun agent trouvé</span>');
  } else {
    matches.forEach(function(agent) {
      $dropdown.append(
        '<a href="#" class="dropdown-item ticket-agent-option" data-agent-ref="' +
        $('<div>').text(agent.id).html() +
        '" data-agent-label="' +
        $('<div>').text(agent.label).html() +
        '">' +
        $('<div>').text(agent.label).html() +
        '</a>'
      );
    });
  }

  $dropdown.show();
}

function remplirAgentsTicket(groupeId, selectedAgentRef) {
  var $search = $('#ticket_agent_search');
  var groupeData = agentsParGroupe[groupeId] || {};
  agentsTicketCourants = groupeData.agents || [];

  resetAgentTicketSelection();

  if (!groupeId) {
    $search.prop('disabled', true).attr('placeholder', 'Sélectionnez d\'abord un groupe...');
    $('#ticket_agent_dropdown').hide();
    return;
  }

  if (agentsTicketCourants.length === 0) {
    var videLabel = (groupeData.source || 'api') === 'local'
      ? 'Aucun agent enregistré pour ce groupe'
      : 'Aucun agent trouvé pour ce groupe';
    $search.prop('disabled', true).attr('placeholder', videLabel);
    $('#ticket_agent_dropdown').hide();
    return;
  }

  $search.prop('disabled', false).attr('placeholder', 'Tapez numéro ou nom d\'agent...');

  if (selectedAgentRef) {
    var label = agentLabelParRef(selectedAgentRef);
    if (label) {
      selectAgentTicket(selectedAgentRef, label);
    }
  }
}

function initAgentTicketAutocomplete() {
  var groupeInit = oldParticulierGroupeId || $('#ticket_particulier_groupe_id').val();
  if (groupeInit) {
    remplirAgentsTicket(groupeInit, oldAgentRef || $('#ticket_agent_ref').val());
  }
}

$(document).ready(function() {
  $('#modalAddTicket').on('shown.bs.modal', function() {
    if ($('#ticket_id_usine').hasClass('select2-hidden-accessible')) {
      $('#ticket_id_usine').select2('destroy');
    }
    if ($('#ticket_particulier_groupe_id').hasClass('select2-hidden-accessible')) {
      $('#ticket_particulier_groupe_id').select2('destroy');
    }

    initVehiculeTicketAutocomplete();
    initAgentTicketAutocomplete();

    if ($('#ticket_id_pont').hasClass('select2-hidden-accessible')) {
      $('#ticket_id_pont').select2('destroy');
    }
    $('#ticket_id_pont').select2({
      theme: 'bootstrap-5',
      dropdownParent: $('#modalAddTicket .modal-body'),
      placeholder: '-- Aucun pont --',
      allowClear: true,
      width: '100%',
      templateResult: function(option) {
        var text = option.text || '';
        if (text.indexOf('★') !== -1) {
          var safe = text.replace(/[&<>"]/g, function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]);});
          var html = safe.replace('★', '<span style="color:red;">★</span>');
          return $('' + html + '');
        }
        return option.text;
      },
      templateSelection: function(option) {
        var text = option.text || '';
        if (text.indexOf('★') !== -1) {
          var safe = text.replace(/[&<>"]/g, function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]);});
          var html = safe.replace('★', '<span style="color:red;">★</span>');
          return $('' + html + '');
        }
        return option.text;
      }
    });

    if ($('#ticket_id_usine').hasClass('select2-hidden-accessible')) {
      $('#ticket_id_usine').select2('destroy');
    }
    $('#ticket_id_usine').select2({
      theme: 'bootstrap-5',
      dropdownParent: $('#modalAddTicket .modal-body'),
      placeholder: '-- Sélectionner une usine --',
      allowClear: true,
      width: '100%'
    });

    $('#ticket_particulier_groupe_id').select2({
      theme: 'bootstrap-5',
      dropdownParent: $('#modalAddTicket .modal-body'),
      placeholder: '-- Sélectionner un groupe --',
      allowClear: true,
      width: '100%'
    });

    syncParcColumnVisibility();
    if ($('#ticket_produit_id').val()) {
      onProduitChangeUsine();
      onProduitChange();
      if (oldParcId) {
        $('#ticket_parc_id').val(String(oldParcId));
      }
    }
    syncTicketSubmitButton();
  });

  $('#ticket_particulier_groupe_id').on('change', function() {
    remplirAgentsTicket($(this).val(), null);
  });

  $('#ticket_agent_search').on('input focus', function() {
    if ($('#ticket_agent_ref').val() && $(this).val() === agentLabelParRef($('#ticket_agent_ref').val())) {
      $(this).val('');
      $('#ticket_agent_ref').val('');
    }
    renderAgentsTicketDropdown();
  });

  $(document).on('click', '.ticket-agent-option', function(e) {
    e.preventDefault();
    selectAgentTicket($(this).attr('data-agent-ref'), $(this).attr('data-agent-label'));
  });

  $(document).on('click', function(e) {
    if (!$(e.target).closest('#ticket_agent_search, #ticket_agent_dropdown').length) {
      $('#ticket_agent_dropdown').hide();
    }
  });

  $('#modalAddTicket form').on('submit', function(e) {
    if (!$('#ticket_agent_ref').val()) {
      e.preventDefault();
      $('#ticket_agent_search').addClass('is-invalid').focus();
      renderAgentsTicketDropdown();
      return false;
    }
    $('#ticket_agent_search').removeClass('is-invalid');

    if (!validerParcGerable()) {
      e.preventDefault();
      return false;
    }
  });

  $('#ticket_vehicule_search').on('input focus', function() {
    renderVehiculesTicketDropdown();
  });

  $(document).on('click', '.ticket-vehicule-option', function(e) {
    e.preventDefault();
    selectVehiculeTicket($(this).data('matricule'), $(this).data('vehicule-id'));
  });

  $(document).on('click', function(e) {
    if (!$(e.target).closest('#ticket_vehicule_search, #ticket_vehicule_dropdown').length) {
      $('#ticket_vehicule_dropdown').hide();
    }
  });

  $('#modalAddTicket form').on('submit', function(e) {
    var matricule = $('#ticket_vehicule_search').val().trim();
    var vehiculeId = vehiculesTicketMap[matricule];

    if (!matricule || vehiculeId === undefined || vehiculeId === '') {
      e.preventDefault();
      alert('Veuillez sélectionner un véhicule valide dans la liste.');
      $('#ticket_vehicule_search').focus();
      return false;
    }

    $('#ticket_matricule_vehicule').val(matricule);
    $('#ticket_vehicule_id').val(vehiculeId);

    var $submitBtn = $(this).find('button[type="submit"]');
    if ($submitBtn.prop('disabled')) {
      e.preventDefault();
      return false;
    }
    $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…');
  });

  $('#modalAddTicket').on('hidden.bs.modal', function() {
    if (!oldMatriculeVehicule) {
      $('#ticket_vehicule_search').val('');
      $('#ticket_matricule_vehicule').val('');
      $('#ticket_vehicule_id').val('');
    }
    $('#ticket_vehicule_dropdown').hide();
    $('#modalAddTicket form button[type="submit"]')
      .html('<i class="bx bx-check me-1"></i>Enregistrer');
    syncTicketSubmitButton();
  });

  // Usines par produit
  var usinesParProduit = @json($usinesParProduit ?? []);

  function onProduitChangeUsine() {
    var produitId = $('#ticket_produit_id').val();
    var $usine = $('#ticket_id_usine');
    var oldUsineVal = $usine.val();

    $usine.empty();

    if (!produitId) {
      $usine.append('<option value="">-- Sélectionner d\'abord un produit --</option>');
    } else {
      var usines = usinesParProduit[produitId] || [];
      $usine.append('<option value="">-- Sélectionner une usine --</option>');
      usines.forEach(function(u) {
        $usine.append('<option value="' + u.id_usine + '">' + u.nom + '</option>');
      });
    }

    if ($usine.hasClass('select2-hidden-accessible')) {
      $usine.trigger('change.select2');
    }
    syncTicketSubmitButton();
  }

  function formulaireTicketComplet() {
    var dateOk = !!$('input[name="date_ticket"]').val();
    var numeroOk = !!$('input[name="numero_ticket"]').val().trim();
    var matricule = $('#ticket_vehicule_search').val().trim();
    var vehiculeOk = !!(matricule && vehiculesTicketMap[matricule]);
    var usineOk = !!$('#ticket_id_usine').val();
    var groupeOk = !!$('#ticket_particulier_groupe_id').val();
    var agentOk = !!$('#ticket_agent_ref').val();
    var gerable = pontEstGerable();
    var produitOk = !gerable || !!$('#ticket_produit_id').val();
    var parcOk = true;

    if (gerable) {
      var $parc = $('#ticket_parc_id');
      parcOk = !$parc.prop('disabled') && !!$parc.val();
    }

    return dateOk && numeroOk && vehiculeOk && usineOk && groupeOk && agentOk && produitOk && parcOk;
  }

  function syncTicketSubmitButton() {
    var $btn = $('#btnSubmitTicket');
    $btn.prop('disabled', !formulaireTicketComplet());
  }

  // Pont → Produit → Parc
  var parcsParPontProduit = @json($parcsParPontProduit ?? []);

  function pontEstGerable() {
    var idPont = $('#ticket_id_pont').val();
    return !!(idPont && pontGerableParId[String(idPont)]);
  }

  function syncParcColumnVisibility() {
    if (pontEstGerable()) {
      $('#col_parc').show();
    } else {
      $('#col_parc').hide();
    }
    syncParcObligatoire();
  }

  function syncParcObligatoire() {
    var gerable = pontEstGerable();
    $('#ticket_parc_required').toggleClass('d-none', !gerable);
    $('#ticket_produit_required').toggleClass('d-none', !gerable);
    var $parc = $('#ticket_parc_id');
    $parc.prop('required', gerable && !$parc.prop('disabled'));
    syncTicketSubmitButton();
  }

  function validerParcGerable() {
    if (!pontEstGerable()) {
      $('#ticket_parc_id').removeClass('is-invalid');
      return true;
    }

    var $parc = $('#ticket_parc_id');
    if (!$parc.prop('disabled') && $parc.val()) {
      $parc.removeClass('is-invalid');
      return true;
    }

    $parc.addClass('is-invalid').focus();
    var msg = 'Le parc est obligatoire pour un pont gérable.';
    if ($parc.prop('disabled')) {
      msg = 'Aucun parc disponible pour ce pont et ce produit. Enregistrement impossible.';
    }
    alert(msg);
    return false;
  }

  function onPontChange() {
    var $produit = $('#ticket_produit_id');
    var $parc = $('#ticket_parc_id');

    $produit.val('');
    $parc.empty().append('<option value="">-- Sélectionner d\'abord un produit --</option>').prop('disabled', true);
    onProduitChangeUsine();
    syncParcColumnVisibility();
    onProduitChange();
  }

  function onProduitChange() {
    var idPont = $('#ticket_id_pont').val();
    var gerable = pontEstGerable();
    var produitId = $('#ticket_produit_id').val();
    var $parc = $('#ticket_parc_id');

    $parc.empty();

    if (!gerable) {
      $parc.append('<option value="">-</option>').prop('disabled', true);
      syncParcObligatoire();
      return;
    }

    syncParcColumnVisibility();

    if (!idPont || !produitId) {
      $parc.append('<option value="">-- Sélectionner d\'abord un produit --</option>').prop('disabled', true);
      syncParcObligatoire();
      return;
    }

    var parcs = (parcsParPontProduit[idPont] || {})[produitId] || (parcsParPontProduit[String(idPont)] || {})[produitId] || (parcsParPontProduit[String(idPont)] || {})[String(produitId)] || [];
    if (parcs.length === 0) {
      $parc.append('<option value="">Aucun parc disponible pour ce pont/produit</option>').prop('disabled', true);
    } else {
      $parc.append('<option value="">-- Sélectionner un parc --</option>');
      parcs.forEach(function(p) {
        $parc.append('<option value="' + p.id + '">' + p.nom + ' (' + p.code + ')</option>');
      });
      $parc.prop('disabled', false);
    }
    syncParcObligatoire();
  }

  $('#ticket_parc_id').on('change', function() {
    $(this).removeClass('is-invalid');
    syncTicketSubmitButton();
  });

  $('#modalAddTicket').on('input change', 'input, select', syncTicketSubmitButton);
  $('#ticket_id_usine, #ticket_particulier_groupe_id').on('change select2:select select2:clear', syncTicketSubmitButton);

  $('#ticket_id_pont').on('change select2:select', onPontChange);
  $('#ticket_produit_id').on('change', function() {
    onProduitChange();
    onProduitChangeUsine();
  });

  @if($errors->any() && !$errors->has('numero_ticket'))
    var modalAddTicket = new bootstrap.Modal(document.getElementById('modalAddTicket'));
    modalAddTicket.show();
  @endif
});
</script>
@endsection
