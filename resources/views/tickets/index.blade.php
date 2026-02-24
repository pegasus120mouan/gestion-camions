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
              <th>Prix U</th>
              <th>Conformité</th>
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
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $t['vehicule_id'] ?? 0, 'matricule' => $t['matricule_vehicule'] ?? '']) }}">
                    {{ $t['matricule_vehicule'] ?? '' }}
                  </a>
                </td>
                <td>{{ number_format((float)($t['poids'] ?? 0), 0, ',', ' ') }}</td>
                <td>{{ number_format((float)($t['prix_unitaire_transport'] ?? 0), 0, ',', ' ') }}</td>
                <td>
                  @if($t['conformite'] === 'conforme')
                    <span class="badge bg-success">Conforme</span>
                  @elseif($t['conformite'] === 'non conforme')
                    <span class="badge bg-danger">Non conforme</span>
                  @else
                    <span class="badge bg-secondary">Non vérifié</span>
                  @endif
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalTicketDetail{{ $loop->index }}" title="Voir détails">
                    <i class="bx bx-show"></i>
                  </button>
                  @if($t['conformite'] !== 'conforme')
                  <button type="button" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEditTicket{{ $loop->index }}" title="Modifier">
                    <i class="bx bx-edit"></i>
                  </button>
                  @endif
                  <button type="button" class="btn btn-sm btn-outline-danger me-1" data-bs-toggle="modal" data-bs-target="#modalDeleteTicket{{ $loop->index }}" title="Supprimer">
                    <i class="bx bx-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center">Aucun ticket</td>
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
            <a class="page-link" href="{{ route('tickets.index', ['page' => $currentPage - 1]) }}">Precedent</a>
          </li>

          @for($i = 1; $i <= $lastPage; $i++)
            @if($i == 1 || $i == $lastPage || abs($i - $currentPage) <= 2)
              <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route('tickets.index', ['page' => $i]) }}">{{ $i }}</a>
              </li>
            @elseif($i == 2 && $currentPage > 4)
              <li class="page-item disabled"><span class="page-link">...</span></li>
            @elseif($i == $lastPage - 1 && $currentPage < $lastPage - 3)
              <li class="page-item disabled"><span class="page-link">...</span></li>
            @endif
          @endfor

          <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route('tickets.index', ['page' => $currentPage + 1]) }}">Suivant</a>
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
                    $codeTransporteurVehicule = \App\Models\CodeTransporteurVehicule::with('codeTransporteur')
                        ->where('matricule_vehicule', $matriculeVehicule)
                        ->first();
                    $transporteurNom = 'Autre';
                    if ($codeTransporteurVehicule && $codeTransporteurVehicule->codeTransporteur) {
                        $transporteurNom = $codeTransporteurVehicule->codeTransporteur->nom;
                    }
                  @endphp
                  <div class="mb-2"><strong>Transporteur:</strong> <span class="badge bg-info">{{ $transporteurNom }}</span></div>
                  <div class="mb-2"><strong>Usine:</strong> {{ $t['nom_usine'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Agent:</strong> {{ ($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? '') }}</div>
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
                  <div class="mb-2"><strong>Prix unitaire:</strong> {{ number_format((float)($t['prix_unitaire'] ?? 0), 0, ',', ' ') }} FCFA</div>
                  <div class="mb-2"><strong>Montant payé:</strong> {{ number_format((float)($t['montant_paie'] ?? 0), 0, ',', ' ') }} FCFA</div>
                  @php
                    // Déterminer le type de transporteur basé sur le code transporteur du véhicule
                    $prixAgentAuto = null;
                    $idAgent = $t['id_agent'] ?? 0;
                    $idUsine = $t['id_usine'] ?? 0;
                    $dateTicket = $t['date_ticket'] ?? $t['date_chargement_fiche'] ?? null;
                    
                    if ($idAgent && $idUsine) {
                        // Déterminer le type basé sur le transporteur
                        $typeTransporteur = 'transporteur'; // par défaut: Camion Agent
                        if ($transporteurNom === 'Camion PGF') {
                            $typeTransporteur = 'pgf';
                        }
                        
                        // Chercher le prix correspondant
                        $queryPrix = \App\Models\PrixAgent::where('id_agent', $idAgent)
                            ->where('id_usine', $idUsine)
                            ->where('type', $typeTransporteur);
                        
                        // Filtrer par date si disponible
                        if ($dateTicket) {
                            $dateTicketParsed = \Carbon\Carbon::parse($dateTicket)->format('Y-m-d');
                            $queryPrix->where(function($q) use ($dateTicketParsed) {
                                $q->where(function($q2) use ($dateTicketParsed) {
                                    $q2->whereNull('date_debut')
                                       ->orWhere('date_debut', '<=', $dateTicketParsed);
                                })->where(function($q3) use ($dateTicketParsed) {
                                    $q3->whereNull('date_fin')
                                       ->orWhere('date_fin', '>=', $dateTicketParsed);
                                });
                            });
                        }
                        
                        $prixAgentRecord = $queryPrix->first();
                        if ($prixAgentRecord) {
                            $prixAgentAuto = $prixAgentRecord->prix;
                        }
                    }
                  @endphp
                  <div class="mb-2"><strong>Prix unitaire Agent:</strong> {{ $prixAgentAuto !== null ? number_format($prixAgentAuto, 0, ',', ' ') . ' FCFA' : '-' }}</div>
                  @php
                    $montantAgentsAuto = null;
                    if ($prixAgentAuto !== null && $poidsUsineModal > 0) {
                        // Poids en kg, prix en FCFA/kg
                        $montantAgentsAuto = $prixAgentAuto * $poidsUsineModal;
                    }
                  @endphp
                  <div class="mb-2"><strong>Montant Agents:</strong> {{ $montantAgentsAuto !== null ? number_format($montantAgentsAuto, 0, ',', ' ') . ' FCFA' : '-' }}</div>
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

<!-- Modals pour modifier les tickets -->
@foreach($tickets as $index => $t)
<div class="modal fade" id="modalEditTicket{{ $index }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Modifier le ticket #{{ $t['numero_ticket'] ?? '' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('tickets.update', $t['id_ticket'] ?? 0) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Numéro Ticket</label>
              <input type="text" class="form-control" name="numero_ticket" value="{{ $t['numero_ticket'] ?? '' }}" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date Ticket</label>
              <input type="date" class="form-control" name="date_ticket" value="{{ $t['date_ticket'] ?? '' }}" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Matricule Véhicule</label>
              <input type="text" class="form-control" name="matricule_vehicule" value="{{ $t['matricule_vehicule'] ?? '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Poids (kg)</label>
              <input type="number" step="0.01" class="form-control" name="poids" value="{{ $t['poids'] ?? '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Poids Parc (kg)</label>
              <input type="number" step="0.01" class="form-control" name="poids_parc" value="{{ $t['poids_parc'] ?? '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Prix Unitaire Transport</label>
              <input type="number" step="0.01" class="form-control" name="prix_unitaire_transport" value="{{ $t['prix_unitaire_transport'] ?? '' }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<!-- Modals pour supprimer les tickets -->
@foreach($tickets as $index => $t)
<div class="modal fade" id="modalDeleteTicket{{ $index }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-trash me-2"></i>Supprimer le ticket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer le ticket <strong>#{{ $t['numero_ticket'] ?? '' }}</strong> ?</p>
        <p class="text-muted mb-0">Cette action est irréversible.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('tickets.destroy', $t['id_ticket'] ?? 0) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger"><i class="bx bx-trash me-1"></i>Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>
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
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">N° Ticket <span class="text-danger">*</span></label>
              <input type="text" name="numero_ticket" class="form-control" required placeholder="Ex: TKT-001" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date Ticket <span class="text-danger">*</span></label>
              <input type="date" name="date_ticket" class="form-control" required value="{{ date('Y-m-d') }}" />
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Véhicule <span class="text-danger">*</span></label>
              <select name="vehicule_id" class="form-select" required>
                <option value="">-- Sélectionner un véhicule --</option>
                @foreach($vehiculesApi ?? [] as $vehiculeItem)
                  <option value="{{ $vehiculeItem['vehicules_id'] ?? '' }}" data-matricule="{{ $vehiculeItem['matricule_vehicule'] ?? '' }}">{{ $vehiculeItem['matricule_vehicule'] ?? '' }}</option>
                @endforeach
              </select>
              <input type="hidden" name="matricule_vehicule" id="matricule_vehicule_hidden" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Poids (kg)</label>
              <input type="number" name="poids" class="form-control" step="0.01" min="0" placeholder="Ex: 15000" />
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Usine <span class="text-danger">*</span></label>
              <select name="id_usine" class="form-select" required>
                <option value="">-- Sélectionner une usine --</option>
                @foreach($usines ?? [] as $usineItem)
                  <option value="{{ $usineItem['id_usine'] ?? '' }}">{{ $usineItem['nom_usine'] ?? '' }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Agent <span class="text-danger">*</span></label>
              <select name="id_agent" class="form-select" required>
                <option value="">-- Sélectionner un agent --</option>
                @foreach($agents ?? [] as $agentItem)
                  <option value="{{ $agentItem['id_agent'] ?? '' }}">{{ $agentItem['nom_complet'] ?? '' }} ({{ $agentItem['numero_agent'] ?? '' }})</option>
                @endforeach
              </select>
            </div>
          </div>
                  </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const vehiculeSelect = document.querySelector('select[name="vehicule_id"]');
  const matriculeHidden = document.getElementById('matricule_vehicule_hidden');
  
  if (vehiculeSelect && matriculeHidden) {
    vehiculeSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      matriculeHidden.value = selectedOption.dataset.matricule || '';
    });
  }

  // Initialiser Select2 pour les selects du modal
  $('#modalAddTicket').on('shown.bs.modal', function() {
    $('#modalAddTicket select[name="vehicule_id"]').select2({
      theme: 'bootstrap-5',
      dropdownParent: $('#modalAddTicket'),
      placeholder: '-- Sélectionner un véhicule --',
      allowClear: true
    });
    $('#modalAddTicket select[name="id_usine"]').select2({
      theme: 'bootstrap-5',
      dropdownParent: $('#modalAddTicket'),
      placeholder: '-- Sélectionner une usine --',
      allowClear: true
    });
    $('#modalAddTicket select[name="id_agent"]').select2({
      theme: 'bootstrap-5',
      dropdownParent: $('#modalAddTicket'),
      placeholder: '-- Sélectionner un agent --',
      allowClear: true
    });
  });

  // Mettre à jour le champ caché matricule quand on change de véhicule via Select2
  $(document).on('select2:select', 'select[name="vehicule_id"]', function(e) {
    var selectedOption = $(this).find(':selected');
    $('#matricule_vehicule_hidden').val(selectedOption.data('matricule') || '');
  });
});
</script>
@endsection
