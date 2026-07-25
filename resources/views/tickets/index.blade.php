@extends('layout.main')
@section('title', !empty($onlyCamionsPgf) ? 'Activités camions PGF' : 'Tickets')

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
  }
  .table-activites-pgf {
    font-size: 0.8125rem;
  }
  .table-activites-pgf th {
    font-size: 0.75rem;
    letter-spacing: 0.02em;
  }
  .table-activites-pgf .form-control-sm {
    font-size: 0.8125rem;
    padding-top: 0.2rem;
    padding-bottom: 0.2rem;
  }
  .table-activites-pgf .btn-attente-prix {
    color: #dc3545;
    font-weight: 600;
    font-size: 0.8125rem;
    padding: 0.15rem 0.5rem;
    border: 1px solid rgba(220, 53, 69, 0.35);
    background: #fff;
  }
  .table-activites-pgf .btn-attente-prix:hover {
    color: #fff;
    background: #dc3545;
    border-color: #dc3545;
  }
  .table-activites-pgf .btn-prix-saisi {
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
  }
  .table-activites-pgf .badge-statut-pgf {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
  }
</style>
@endsection

@section('content')
@php
  $ticketsIndexRoute = $ticketsIndexRoute ?? 'tickets.index';
  $ticketsQueryBase = !empty($onlyCamionsPgf)
    ? request()->only(['vehicule', 'agent', 'statut', 'date_debut', 'date_fin', 'numero'])
    : request()->only(['vehicule', 'usine', 'agent', 'statut', 'numero']);
@endphp
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">
          @if (!empty($onlyCamionsPgf))
            Activités camions PGF
          @elseif (!empty($onlyLocaux))
            Mes tickets locaux
          @elseif (!empty($enAttenteOnly))
            Mes tickets en attente
          @else
            Mes tickets Unipalm
          @endif
        </h4>
        @if (!empty($onlyCamionsPgf))
          <small class="text-muted">Tickets liés aux véhicules du groupe PGF</small>
        @elseif (!empty($onlyLocaux))
          <small class="text-muted">Tickets créés localement (hors API Unipalm)</small>
        @endif
      </div>
      @if (!empty($onlyLocaux))
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddTicket">
        <i class="bx bx-plus me-1"></i>Ajouter un ticket
      </button>
      @endif
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

    @if (!empty($onlyCamionsPgf))
    @php
      $filtreActif = collect(['numero', 'vehicule', 'agent', 'statut', 'date_debut', 'date_fin'])
        ->contains(fn ($key) => filled(request($key)));
    @endphp
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-header bg-transparent border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="avatar avatar-sm rounded bg-label-primary">
              <i class="bx bx-filter-alt"></i>
            </span>
            <div>
              <h6 class="mb-0">Filtres de recherche</h6>
              <small class="text-muted">Période, véhicule, agent et statut de paiement</small>
            </div>
          </div>
          @if ($filtreActif)
            <span class="badge bg-label-primary">Filtres actifs</span>
          @endif
        </div>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route($ticketsIndexRoute) }}" class="row g-3 align-items-end">
          <div class="col-md-3 col-lg-2">
            <label class="form-label small text-uppercase text-muted mb-1" for="filtre_date_debut">Du</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="bx bx-calendar"></i></span>
              <input type="date" name="date_debut" id="filtre_date_debut" class="form-control" value="{{ request('date_debut') }}">
            </div>
          </div>
          <div class="col-md-3 col-lg-2">
            <label class="form-label small text-uppercase text-muted mb-1" for="filtre_date_fin">Au</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="bx bx-calendar"></i></span>
              <input type="date" name="date_fin" id="filtre_date_fin" class="form-control" value="{{ request('date_fin') }}">
            </div>
          </div>
          <div class="col-md-3 col-lg-2">
            <label class="form-label small text-uppercase text-muted mb-1" for="filtre_numero">N° ticket</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="bx bx-barcode"></i></span>
              <input type="text" name="numero" id="filtre_numero" class="form-control" placeholder="N° ticket..." value="{{ request('numero') }}" autocomplete="off" />
            </div>
          </div>
          <div class="col-md-3 col-lg-2">
            <label class="form-label small text-uppercase text-muted mb-1" for="vehicule_input">Véhicule</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="bx bx-car"></i></span>
              <input type="text" name="vehicule" id="vehicule_input" class="form-control" placeholder="Matricule..." value="{{ request('vehicule') }}" list="vehicules_list" autocomplete="off" />
            </div>
            <datalist id="vehicules_list">
              @foreach(collect($vehiculesPgf ?? []) as $v)
                @php
                  $matriculeOpt = $v['matricule_vehicule'] ?? $v['matricule'] ?? null;
                @endphp
                @if ($matriculeOpt)
                  <option value="{{ $matriculeOpt }}">
                @endif
              @endforeach
            </datalist>
          </div>
          <div class="col-md-3 col-lg-2">
            <label class="form-label small text-uppercase text-muted mb-1" for="filtre_agent">Agent</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="bx bx-user"></i></span>
              <input type="text" name="agent" id="filtre_agent" class="form-control" placeholder="Nom agent..." value="{{ request('agent') }}" autocomplete="off" />
            </div>
          </div>
          <div class="col-md-3 col-lg-2">
            <label class="form-label small text-uppercase text-muted mb-1" for="filtre_statut">Statut</label>
            <select name="statut" id="filtre_statut" class="form-select">
              <option value="" @selected(request('statut', '') === '')>Tous</option>
              <option value="non_paye" @selected(request('statut') === 'non_paye')>Non payé</option>
              <option value="paye" @selected(request('statut') === 'paye')>Payé</option>
            </select>
          </div>
          <div class="col-md-9 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">
              <i class="bx bx-search me-1"></i>Rechercher
            </button>
            <a href="{{ route($ticketsIndexRoute) }}" class="btn btn-outline-secondary" title="Réinitialiser">
              <i class="bx bx-reset"></i>
            </a>
          </div>
        </form>
      </div>
    </div>
    @else
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route($ticketsIndexRoute) }}" class="row g-3">
          @if(!empty($enAttenteOnly))
            <input type="hidden" name="statut" value="en_attente" />
          @endif
          <div class="col-md-2">
            <label class="form-label">N° ticket</label>
            <input type="text" name="numero" class="form-control" placeholder="N° ticket..." value="{{ request('numero') }}" autocomplete="off" />
          </div>
          <div class="col-md-2">
            <label class="form-label">Vehicule</label>
            <input type="text" name="vehicule" id="vehicule_input" class="form-control" placeholder="Matricule..." value="{{ request('vehicule') }}" list="vehicules_list" autocomplete="off" />
            <datalist id="vehicules_list">
              @foreach(($vehicules ?? []) as $matricule)
                <option value="{{ $matricule }}">
              @endforeach
            </datalist>
          </div>
          <div class="col-md-2">
            <label class="form-label">Usine</label>
            <input type="text" name="usine" class="form-control" placeholder="Nom usine..." value="{{ request('usine') }}" />
          </div>
          <div class="col-md-2">
            <label class="form-label">Agent</label>
            <input type="text" name="agent" class="form-control" placeholder="Nom agent..." value="{{ request('agent') }}" />
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route($ticketsIndexRoute, !empty($enAttenteOnly) ? ['statut' => 'en_attente'] : []) }}" class="btn btn-outline-secondary">Reinitialiser</a>
          </div>
        </form>
      </div>
    </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table {{ !empty($onlyCamionsPgf) ? 'table-activites-pgf' : '' }}">
          <thead>
            <tr>
              <th>Date ticket</th>
              <th>N°Ticket</th>
              <th>Usine</th>
              @if (empty($onlyCamionsPgf))
              <th>Produit</th>
              @endif
              <th>Agent</th>
              <th>Pont</th>
              <th>Vehicule</th>
              <th>Poids Usine</th>
              @if (!empty($onlyCamionsPgf))
              <th class="text-end">Prix unitaire</th>
              <th class="text-end">Montant</th>
              <th>N° Bordereau</th>
              @else
              <th>Actions</th>
              @endif
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($tickets as $t)
              @php
                $poidsLigne = (float) ($t['poids'] ?? 0);
                $ticketValide = ($t['conformite'] ?? '') === 'valide';
                $estCamionPgf = (bool) ($t['est_camion_pgf'] ?? false);
                $prixManuel = array_key_exists('prix_unitaire_manuel', $t) ? $t['prix_unitaire_manuel'] : null;
                $montantManuel = array_key_exists('montant_manuel', $t) ? $t['montant_manuel'] : null;
                if ($montantManuel === null && $prixManuel !== null && $poidsLigne > 0) {
                  $montantManuel = (float) $prixManuel * $poidsLigne;
                }
                $surBordereau = !empty($t['bordereau_pgf_id']);
                $prixAffiche = $prixManuel !== null
                  ? rtrim(rtrim(number_format((float) $prixManuel, 2, '.', ''), '0'), '.')
                  : '';
              @endphp
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
                @if (empty($onlyCamionsPgf))
                <td>{{ $t['nom_produit'] ?? '-' }}</td>
                @endif
                <td>{{ $t['nom_agent'] ?? '-' }}</td>
                <td>{{ $t['nom_pont'] ?? ($t['origine'] ?? '-') }}</td>
                <td>
                  @if(!empty($t['vehicule_id']))
                    <a href="{{ route('vehicules.depenses', ['vehicule_id' => $t['vehicule_id'], 'matricule' => $t['matricule_vehicule'] ?? '']) }}">
                      {{ $t['matricule_vehicule'] ?? '' }}
                    </a>
                  @else
                    {{ $t['matricule_vehicule'] ?? '-' }}
                  @endif
                </td>
                <td class="text-end">{{ number_format($poidsLigne, 0, ',', ' ') }}</td>
                @if (!empty($onlyCamionsPgf))
                <td style="min-width: 120px;" class="js-prix-cell-pgf text-end"
                    data-ticket-id="{{ $t['id_ticket'] }}"
                    data-ticket-numero="{{ $t['numero_ticket'] ?? '' }}"
                    data-poids="{{ $poidsLigne }}"
                    data-save-url="{{ route('tickets.prix_unitaire', $t['id_ticket']) }}"
                    data-prix="{{ $prixAffiche }}">
                  @if ($surBordereau)
                    @if ($prixManuel !== null)
                      <span class="badge bg-label-primary" title="Prix figé (ticket déjà sur bordereau)">{{ $prixAffiche }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  @elseif ($prixManuel === null)
                    <button type="button" class="btn btn-sm btn-attente-prix js-open-prix-modal">
                      En attente
                    </button>
                  @else
                    <button type="button" class="btn btn-sm btn-outline-primary btn-prix-saisi js-open-prix-modal" title="Modifier le prix unitaire">
                      {{ $prixAffiche }}
                    </button>
                  @endif
                </td>
                <td class="text-end fw-semibold js-montant-pgf" data-ticket-id="{{ $t['id_ticket'] }}">
                  {{ $montantManuel !== null ? number_format((float) $montantManuel, 0, ',', ' ').' FCFA' : '—' }}
                </td>
                <td>
                  @if(!empty($t['numero_bordereau']) && !empty($t['bordereau_pgf_id']))
                    <a href="{{ route('camions.revenues.bordereau.pdf', ['id' => $t['bordereau_pgf_id']]) }}" target="_blank" rel="noopener" class="fw-semibold text-primary text-decoration-none">
                      {{ $t['numero_bordereau'] }}
                    </a>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                @else
                <td class="js-actions-pgf">
                  <a href="{{ route('tickets.pdf', ['id' => $t['id_ticket']]) }}" class="btn btn-sm btn-outline-secondary me-1" target="_blank" rel="noopener" title="Imprimer en PDF">
                    <i class="bx bx-printer"></i>
                  </a>
                  @if (!empty($onlyLocaux))
                    <span class="badge bg-label-info me-1">Local</span>
                    <form method="POST" action="{{ route('tickets.destroy', $t['id_ticket']) }}" class="d-inline" onsubmit="return confirm('Supprimer ce ticket local ?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                        <i class="bx bx-trash"></i>
                      </button>
                    </form>
                  @else
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
                  @endif
                </td>
                @endif
              </tr>
            @empty
              <tr>
                <td colspan="{{ !empty($onlyCamionsPgf) ? 10 : 9 }}" class="text-center">Aucun ticket</td>
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
            <a class="page-link" href="{{ route($ticketsIndexRoute, array_merge($ticketsQueryBase, ['page' => $currentPage - 1])) }}">Precedent</a>
          </li>

          @for($i = 1; $i <= $lastPage; $i++)
            @if($i == 1 || $i == $lastPage || abs($i - $currentPage) <= 2)
              <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route($ticketsIndexRoute, array_merge($ticketsQueryBase, ['page' => $i])) }}">{{ $i }}</a>
              </li>
            @elseif($i == 2 && $currentPage > 4)
              <li class="page-item disabled"><span class="page-link">...</span></li>
            @elseif($i == $lastPage - 1 && $currentPage < $lastPage - 3)
              <li class="page-item disabled"><span class="page-link">...</span></li>
            @endif
          @endfor

          <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route($ticketsIndexRoute, array_merge($ticketsQueryBase, ['page' => $currentPage + 1])) }}">Suivant</a>
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
                        $estCamionPgf = \App\Models\GroupeVehicule::query()
                            ->where('matricule_vehicule', $matriculeVehicule)
                            ->whereHas('groupe', fn ($query) => $query->where('nom_groupe', 'PGF'))
                            ->exists();
                        if ($estCamionPgf) {
                            $transporteurNom = 'Camion PGF';
                        }
                    }
                  @endphp
                  <div class="mb-2"><strong>Transporteur:</strong> <span class="badge bg-info">{{ $transporteurNom }}</span></div>
                  <div class="mb-2"><strong>Usine:</strong> {{ $t['nom_usine'] ?? '-' }}</div>
                  <div class="mb-2"><strong>Pont:</strong> {{ $t['nom_pont'] ?? ($t['origine'] ?? '-') }}</div>
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

<!-- Modals validation ticket -->
@foreach($tickets as $index => $t)
  @php
    $ticketValideModal = ($t['conformite'] ?? '') === 'valide';
    $estCamionPgf = (bool) ($t['est_camion_pgf'] ?? false);
    $fichesPourTicket = collect($t['fiches_correspondantes'] ?? []);
    $nomProduitModal = $t['nom_produit'] ?? '-';
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
          <input type="hidden" name="confirm_validation" value="1" />
          <div class="modal-body">
            <div class="alert alert-light border mb-3">
              <div class="row g-2">
                <div class="col-md-3"><strong>Ticket :</strong> {{ $t['numero_ticket'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Véhicule :</strong> {{ $t['matricule_vehicule'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Agent :</strong> {{ $t['nom_agent'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Pont :</strong> {{ $t['nom_pont'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Usine :</strong> {{ $t['nom_usine'] ?? '-' }}</div>
                <div class="col-md-3"><strong>Produit :</strong> {{ $nomProduitModal }}</div>
                <div class="col-md-3"><strong>Poids :</strong> {{ number_format((float)($t['poids'] ?? 0), 0, ',', ' ') }} kg</div>
              </div>
            </div>

            @if($estCamionPgf)
              <p class="text-muted small mb-3">
                <i class="bx bx-info-circle me-1"></i>Camion PGF — sélectionnez une fiche de sortie non déchargée (véhicule, agent, pont et usine identiques au ticket).
              </p>
              <h6 class="mb-3">Fiches de sortie non déchargées</h6>

              @if($fichesPourTicket->isEmpty())
                <div class="alert alert-warning mb-0">
                  Aucune fiche de sortie ne correspond à ce ticket.
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
                Ce camion n'est pas PGF. Le ticket sera validé directement (produit déterminé selon l'usine).
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
          <div id="ticket_fiche_pgf_hint" class="alert alert-info d-none py-2 mb-3" role="alert">
            <i class="bx bx-info-circle me-1"></i>
            <strong>Camion PGF :</strong> après Enregistrer, vous choisirez une fiche de sortie à associer puis validerez.
          </div>
          @error('fiche_id')
            <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
          @enderror
          <input type="hidden" name="fiche_id" id="ticket_fiche_id" value="{{ old('fiche_id') }}" />
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
          <div class="row" id="row_pont_ticket">
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
          {{-- Ligne 5 : Usine (filtrée par produit ; optionnelle si fiche PGF associée) --}}
          <div class="row" id="row_usine_ticket">
            <div class="col-md-12 mb-3">
              <label class="form-label">Usine <span class="text-danger" id="ticket_usine_required">*</span></label>
              <select name="id_usine" id="ticket_id_usine" class="form-select" required>
                <option value="">-- Sélectionner d'abord un produit --</option>
              </select>
              <small class="text-muted d-none" id="ticket_usine_pgf_hint">Camion PGF : après Enregistrer, vous associerez une fiche de sortie.</small>
            </div>
          </div>
          {{-- Ligne 6 : Groupe + Agent --}}
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Groupe <span class="text-danger">*</span></label>
              <select name="groupe_type" id="ticket_groupe_type" class="form-select" required>
                <option value="">-- Sélectionner un groupe --</option>
                <option value="pgf" @selected(old('groupe_type') === 'pgf')>PGF</option>
                <option value="autres" @selected(old('groupe_type') === 'autres')>Autres</option>
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

{{-- Étape 2 : association fiche de sortie (camion PGF) après Enregistrer --}}
<div class="modal fade" id="modalAssocierFicheTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-link me-2"></i>Associer une fiche de sortie
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light border mb-3" id="assocFicheTicketResume"></div>
        <p class="text-muted small mb-3">
          <i class="bx bx-info-circle me-1"></i>Camion PGF — sélectionnez une fiche de sortie non déchargée du même véhicule, puis validez.
        </p>
        <div id="assocFicheTicketEmpty" class="alert alert-warning d-none mb-0">
          Aucune fiche de sortie non déchargée ne correspond à ce véhicule.
        </div>
        <div id="assocFicheTicketTableWrap" class="table-responsive d-none">
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
            <tbody id="assocFicheTicketTbody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Retour</button>
        <button type="button" class="btn btn-success" id="btnValiderAssocFicheTicket" disabled>
          <i class="bx bx-check me-1"></i>Valider
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var agentsParGroupe = @json($agentsParGroupe ?? []);
var agentsTicketCourants = [];
var oldGroupeType = @json(old('groupe_type'));
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
var vehiculesPgfLookup = @json($vehiculesPgfLookup ?? ['ids' => [], 'matricules' => []]);
var fichesDisponiblesAssociation = @json(($fichesDisponiblesAssociation ?? collect())->values());
var oldFicheId = @json(old('fiche_id'));
var ticketPgfForceSubmit = false;
var vehiculesPgfIds = vehiculesPgfLookup.ids || {};
var vehiculesPgfMatricules = {};
Object.keys(vehiculesPgfLookup.matricules || {}).forEach(function(m) {
  vehiculesPgfMatricules[String(m).toUpperCase()] = true;
});

function vehiculeSelectionneEstPgf() {
  var matricule = $('#ticket_vehicule_search').val().trim();
  var vehiculeId = parseInt(vehiculesTicketMap[matricule] || $('#ticket_vehicule_id').val() || 0, 10);
  if (vehiculeId > 0 && vehiculesPgfIds[vehiculeId]) {
    return true;
  }
  var key = matricule.toUpperCase();
  return key !== '' && !!vehiculesPgfMatricules[key];
}

function fichesPourVehiculeSelectionne() {
  var matricule = ($('#ticket_vehicule_search').val() || '').trim().toUpperCase();
  var vehiculeId = parseInt(vehiculesTicketMap[$('#ticket_vehicule_search').val().trim()] || $('#ticket_vehicule_id').val() || 0, 10);
  return (fichesDisponiblesAssociation || []).filter(function(f) {
    if (vehiculeId > 0 && parseInt(f.vehicule_id || 0, 10) === vehiculeId) {
      return true;
    }
    return matricule !== '' && String(f.matricule_vehicule || '').toUpperCase() === matricule;
  });
}

function syncFichePgfObligatoire() {
  var estPgf = vehiculeSelectionneEstPgf();
  $('#ticket_fiche_pgf_hint').toggleClass('d-none', !estPgf);
  // Pont / produit / usine restent saisissables ; la fiche s’associe après Enregistrer.
  $('#row_pont_ticket, #col_produit').show();
  $('#ticket_usine_required').removeClass('d-none');
  $('#ticket_usine_pgf_hint').toggleClass('d-none', !estPgf);
  $('#ticket_id_usine').prop('required', true);
  if (!estPgf) {
    $('#ticket_fiche_id').val('');
  } else if (!oldFicheId) {
    $('#ticket_fiche_id').val('');
  }
  syncParcColumnVisibility();
  syncParcObligatoire();
}

function masquerChargementPage() {
  var overlay = document.getElementById('page-loading-overlay');
  if (overlay) {
    overlay.classList.add('is-hidden');
  }
}

function ouvrirModalAssocierFicheTicket() {
  masquerChargementPage();
  var fiches = fichesPourVehiculeSelectionne();
  var numero = $('input[name="numero_ticket"]').val() || '—';
  var matricule = $('#ticket_vehicule_search').val() || '—';
  var poids = $('input[name="poids"]').val() || '—';
  var agent = $('#ticket_agent_search').val() || '—';

  $('#assocFicheTicketResume').html(
    '<div class="row g-2">' +
      '<div class="col-md-3"><strong>Ticket :</strong> ' + $('<div>').text(numero).html() + '</div>' +
      '<div class="col-md-3"><strong>Véhicule :</strong> ' + $('<div>').text(matricule).html() + '</div>' +
      '<div class="col-md-3"><strong>Agent :</strong> ' + $('<div>').text(agent).html() + '</div>' +
      '<div class="col-md-3"><strong>Poids :</strong> ' + $('<div>').text(poids).html() + ' kg</div>' +
    '</div>'
  );

  var $tbody = $('#assocFicheTicketTbody').empty();
  var selected = oldFicheId || $('#ticket_fiche_id').val() || '';
  if (fiches.length === 0) {
    $('#assocFicheTicketEmpty').removeClass('d-none');
    $('#assocFicheTicketTableWrap').addClass('d-none');
    $('#btnValiderAssocFicheTicket').prop('disabled', true);
  } else {
    $('#assocFicheTicketEmpty').addClass('d-none');
    $('#assocFicheTicketTableWrap').removeClass('d-none');
    fiches.forEach(function(f, idx) {
      var checked = (selected && String(selected) === String(f.id)) || (!selected && idx === 0);
      var poidsTxt = f.poids_pont
        ? Number(f.poids_pont).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' kg'
        : '—';
      $tbody.append(
        '<tr>' +
          '<td><input type="radio" name="assoc_fiche_radio" class="form-check-input" value="' + f.id + '"' + (checked ? ' checked' : '') + ' /></td>' +
          '<td>' + $('<div>').text(f.numero_fiche || ('#' + f.id)).html() + '</td>' +
          '<td>' + $('<div>').text(f.date_chargement || '—').html() + '</td>' +
          '<td>' + $('<div>').text(f.matricule_vehicule || '—').html() + '</td>' +
          '<td>' + $('<div>').text(f.nom_agent || '—').html() + '</td>' +
          '<td>' + $('<div>').text(f.nom_pont || '—').html() + '</td>' +
          '<td>' + $('<div>').text(f.usine || '—').html() + '</td>' +
          '<td>' + poidsTxt + '</td>' +
        '</tr>'
      );
    });
    $('#btnValiderAssocFicheTicket').prop('disabled', !$('input[name="assoc_fiche_radio"]:checked').length);
  }

  var addModalEl = document.getElementById('modalAddTicket');
  var assocModalEl = document.getElementById('modalAssocierFicheTicket');
  var addModal = bootstrap.Modal.getInstance(addModalEl) || new bootstrap.Modal(addModalEl);
  var assocModal = bootstrap.Modal.getOrCreateInstance(assocModalEl);
  addModal.hide();
  assocModal.show();
  oldFicheId = null;
}

function syncVehiculeTicketHiddenFields() {
  var matricule = $('#ticket_vehicule_search').val().trim();
  var vehiculeId = vehiculesTicketMap[matricule] || '';
  $('#ticket_matricule_vehicule').val(matricule);
  $('#ticket_vehicule_id').val(vehiculeId);
  syncFichePgfObligatoire();
}

function selectVehiculeTicket(matricule, vehiculeId) {
  $('#ticket_vehicule_search').val(matricule);
  $('#ticket_matricule_vehicule').val(matricule);
  $('#ticket_vehicule_id').val(vehiculeId);
  $('#ticket_vehicule_dropdown').hide();
  syncFichePgfObligatoire();
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
  syncFichePgfObligatoire();
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
  var groupeInit = oldGroupeType || $('#ticket_groupe_type').val();
  if (groupeInit) {
    remplirAgentsTicket(groupeInit, oldAgentRef || $('#ticket_agent_ref').val());
  }
}

$(document).ready(function() {
  $('#modalAddTicket').on('shown.bs.modal', function() {
    if ($('#ticket_id_usine').hasClass('select2-hidden-accessible')) {
      $('#ticket_id_usine').select2('destroy');
    }
    if ($('#ticket_groupe_type').hasClass('select2-hidden-accessible')) {
      $('#ticket_groupe_type').select2('destroy');
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

    $('#ticket_groupe_type').select2({
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

  $('#ticket_groupe_type').on('change', function() {
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

    if (vehiculeSelectionneEstPgf()) {
      if (!ticketPgfForceSubmit) {
        e.preventDefault();
        ouvrirModalAssocierFicheTicket();
        return false;
      }
      if (!$('#ticket_fiche_id').val()) {
        e.preventDefault();
        ticketPgfForceSubmit = false;
        ouvrirModalAssocierFicheTicket();
        return false;
      }
    } else if (!validerParcGerable()) {
      e.preventDefault();
      return false;
    }
  });

  $(document).on('change', 'input[name="assoc_fiche_radio"]', function() {
    $('#btnValiderAssocFicheTicket').prop('disabled', !$(this).val());
  });

  $('#btnValiderAssocFicheTicket').on('click', function() {
    var ficheId = $('input[name="assoc_fiche_radio"]:checked').val();
    if (!ficheId) {
      alert('Sélectionnez une fiche de sortie.');
      return;
    }
    var fiche = (fichesDisponiblesAssociation || []).find(function(f) {
      return String(f.id) === String(ficheId);
    });
    $('#ticket_fiche_id').val(ficheId);
    if (fiche && fiche.poids_pont && !$('input[name="poids"]').val()) {
      $('input[name="poids"]').val(fiche.poids_pont);
    }
    ticketPgfForceSubmit = true;
    var assocModal = bootstrap.Modal.getInstance(document.getElementById('modalAssocierFicheTicket'));
    if (assocModal) {
      assocModal.hide();
    }
    $('#modalAddTicket form').trigger('submit');
  });

  $('#modalAssocierFicheTicket').on('hidden.bs.modal', function() {
    if (!ticketPgfForceSubmit) {
      var addModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAddTicket'));
      addModal.show();
      $('#modalAddTicket form button[type="submit"]')
        .prop('disabled', !formulaireTicketComplet())
        .html('<i class="bx bx-check me-1"></i>Enregistrer');
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
    if (e.isDefaultPrevented()) {
      return false;
    }

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

    // Étape association PGF : ne pas bloquer / spinner tant que la fiche n'est pas choisie.
    if (vehiculeSelectionneEstPgf() && !ticketPgfForceSubmit) {
      return false;
    }

    var $submitBtn = $(this).find('button[type="submit"]');
    if ($submitBtn.prop('disabled') && !ticketPgfForceSubmit) {
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
    var groupeOk = !!$('#ticket_groupe_type').val();
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
    $('#ticket_produit_id').prop('required', gerable);
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
  $('#ticket_id_usine, #ticket_groupe_type').on('change select2:select select2:clear', syncTicketSubmitButton);

  $('#ticket_id_pont').on('change select2:select', onPontChange);
  $('#ticket_produit_id').on('change', function() {
    onProduitChange();
    onProduitChangeUsine();
  });

  @if($errors->any() && !$errors->has('numero_ticket'))
    var modalAddTicket = new bootstrap.Modal(document.getElementById('modalAddTicket'));
    modalAddTicket.show();
    @if($errors->has('fiche_id') || old('fiche_id'))
      setTimeout(function() {
        if (vehiculeSelectionneEstPgf()) {
          ouvrirModalAssocierFicheTicket();
        }
      }, 400);
    @endif
  @endif
});
</script>

@endsection

@section('page-scripts')
@if (!empty($onlyCamionsPgf))
{{-- Modal de saisie du prix unitaire --}}
<div class="modal fade" id="modalSaisirPrixUnitaire" tabindex="-1" aria-labelledby="modalSaisirPrixUnitaireLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title text-white" id="modalSaisirPrixUnitaireLabel">
          <i class="bx bx-edit me-2"></i>Saisir le prix unitaire
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <div class="small text-muted">Ticket</div>
          <div class="fw-semibold" id="modalSaisirPrixTicket">—</div>
        </div>
        <div class="mb-3">
          <div class="small text-muted">Poids usine</div>
          <div class="fw-semibold" id="modalSaisirPrixPoids">—</div>
        </div>
        <div class="mb-3">
          <label for="modalSaisirPrixInput" class="form-label">Prix unitaire (FCFA)</label>
          <input type="text" class="form-control form-control-lg text-end" id="modalSaisirPrixInput" inputmode="decimal" autocomplete="off" placeholder="Ex: 90">
          <div class="invalid-feedback">Indiquez un prix unitaire valide.</div>
        </div>
        <div class="rounded-3 border bg-light p-3 d-flex justify-content-between align-items-center">
          <span class="text-muted">Montant calculé</span>
          <strong class="fs-5" id="modalSaisirPrixMontant">—</strong>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-success" id="modalSaisirPrixValider">
          <i class="bx bx-check me-1"></i>Valider
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal de confirmation --}}
<div class="modal fade" id="modalPrixUnitaireSaisi" tabindex="-1" aria-labelledby="modalPrixUnitaireSaisiLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title text-white" id="modalPrixUnitaireSaisiLabel">
          <i class="bx bx-check-circle me-2"></i>Prix unitaire saisi
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body text-center py-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:72px;height:72px;">
          <i class="bx bx-check fs-1 text-success"></i>
        </div>
        <h5 class="mb-2">Prix unitaire saisi</h5>
        <p class="text-muted mb-1" id="modalPrixUnitaireSaisiTicket">—</p>
        <p class="mb-0 fw-semibold" id="modalPrixUnitaireSaisiDetails">—</p>
      </div>
      <div class="modal-footer border-0 justify-content-center pb-4">
        <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  if (typeof bootstrap === 'undefined') {
    console.error('Bootstrap non chargé : modal prix unitaire indisponible.');
    return;
  }

  var csrfToken = @json(csrf_token());
  var saisirEl = document.getElementById('modalSaisirPrixUnitaire');
  var successEl = document.getElementById('modalPrixUnitaireSaisi');
  if (!saisirEl) return;

  var saisirModal = bootstrap.Modal.getOrCreateInstance(saisirEl);
  var successModal = successEl ? bootstrap.Modal.getOrCreateInstance(successEl) : null;
  var currentCell = null;
  var saving = false;

  var inputEl = document.getElementById('modalSaisirPrixInput');
  var ticketEl = document.getElementById('modalSaisirPrixTicket');
  var poidsEl = document.getElementById('modalSaisirPrixPoids');
  var montantEl = document.getElementById('modalSaisirPrixMontant');
  var validerBtn = document.getElementById('modalSaisirPrixValider');

  function formatMontant(value) {
    var n = Math.round(Number(value) || 0);
    return n.toLocaleString('fr-FR') + ' FCFA';
  }

  function formatPrix(value) {
    var n = Number(value) || 0;
    return n.toLocaleString('fr-FR', { maximumFractionDigits: 2 });
  }

  function parseNombre(value) {
    return parseFloat(String(value || '').replace(/\s/g, '').replace(',', '.')) || 0;
  }

  function refreshMontantPreview() {
    if (!currentCell || !montantEl || !inputEl) return;
    var poids = parseNombre(currentCell.getAttribute('data-poids'));
    var prix = parseNombre(inputEl.value);
    montantEl.textContent = (prix > 0 && poids > 0) ? formatMontant(prix * poids) : '—';
  }

  function openPrixModal(cell) {
    currentCell = cell;
    var ticketId = cell.getAttribute('data-ticket-id');
    var numero = cell.getAttribute('data-ticket-numero') || '';
    var poids = parseNombre(cell.getAttribute('data-poids'));
    var prix = cell.getAttribute('data-prix') || '';

    ticketEl.textContent = numero !== '' ? numero : ('Ticket #' + ticketId);
    poidsEl.textContent = poids > 0 ? (poids.toLocaleString('fr-FR') + ' kg') : '—';
    inputEl.value = prix;
    inputEl.classList.remove('is-invalid');
    refreshMontantPreview();
    saisirModal.show();
    setTimeout(function () {
      inputEl.focus();
      inputEl.select();
    }, 250);
  }

  function updateCellAfterSave(prix, montantAffiche, montant) {
    if (!currentCell) return;
    var ticketId = currentCell.getAttribute('data-ticket-id');
    currentCell.setAttribute('data-prix', String(prix));

    var btn = currentCell.querySelector('.js-open-prix-modal');
    if (btn) {
      btn.className = 'btn btn-sm btn-outline-primary btn-prix-saisi js-open-prix-modal';
      btn.title = 'Modifier le prix unitaire';
      var display = String(prix);
      if (Math.floor(Number(prix)) === Number(prix)) {
        display = String(Math.round(Number(prix)));
      }
      btn.textContent = display;
    }

    var row = currentCell.closest('tr');
    var montantCell = row.querySelector('.js-montant-pgf');
    var montantTxt = montantAffiche || formatMontant(montant);
    if (montantCell) {
      montantCell.textContent = montantTxt;
    }

    document.getElementById('modalPrixUnitaireSaisiTicket').textContent =
      currentCell.getAttribute('data-ticket-numero') || ('Ticket #' + ticketId);
    document.getElementById('modalPrixUnitaireSaisiDetails').textContent =
      'Prix : ' + formatPrix(prix) + ' FCFA  ·  Montant : ' + montantTxt;
  }

  function validerPrix() {
    if (!currentCell || saving) return;
    var url = currentCell.getAttribute('data-save-url');
    var prix = String(inputEl.value || '').trim();
    if (prix === '' || parseNombre(prix) < 0) {
      inputEl.classList.add('is-invalid');
      return;
    }

    inputEl.classList.remove('is-invalid');
    saving = true;
    validerBtn.disabled = true;
    validerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validation...';

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ prix_unitaire: prix }),
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data.success) {
          throw new Error((result.data && result.data.message) || 'Enregistrement impossible');
        }
        updateCellAfterSave(
          result.data.prix_unitaire != null ? result.data.prix_unitaire : parseNombre(prix),
          result.data.montant_affiche,
          result.data.montant
        );
        saisirModal.hide();
        if (successModal) {
          successModal.show();
        }
      })
      .catch(function (error) {
        inputEl.classList.add('is-invalid');
        alert(error.message || 'Erreur lors de l’enregistrement du prix.');
      })
      .finally(function () {
        saving = false;
        validerBtn.disabled = false;
        validerBtn.innerHTML = '<i class="bx bx-check me-1"></i>Valider';
      });
  }

  // Délégation : marche même après mise à jour du bouton.
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('.js-open-prix-modal');
    if (!btn) return;
    event.preventDefault();
    var cell = btn.closest('.js-prix-cell-pgf');
    if (cell) {
      openPrixModal(cell);
    }
  });

  if (inputEl) {
    inputEl.addEventListener('input', refreshMontantPreview);
    inputEl.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        validerPrix();
      }
    });
  }

  if (validerBtn) {
    validerBtn.addEventListener('click', validerPrix);
  }

  saisirEl.addEventListener('hidden.bs.modal', function () {
    currentCell = null;
    if (inputEl) {
      inputEl.value = '';
      inputEl.classList.remove('is-invalid');
    }
    if (montantEl) {
      montantEl.textContent = '—';
    }
  });
})();
</script>
@endif
@endsection
