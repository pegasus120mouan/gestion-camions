@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Mes Tickets</h4>
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
              <th>N°Ticket</th>
              <th>Date chargement</th>
              <th>Date de dechargement</th>
              <th>Vehicule</th>
              <th>Poids Usine</th>
              <th>Montant</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($tickets as $t)
              <tr>
                <td>
                  <a href="#" class="text-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTicketDetail{{ $loop->index }}">
                    {{ $t['numero_ticket'] ?? '' }}
                  </a>
                </td>
                <td>{{ $t['date_chargement_fiche'] ?? '-' }}</td>
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
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $t['vehicule_id'] ?? 0, 'matricule' => $t['matricule_vehicule'] ?? '']) }}">
                    {{ $t['matricule_vehicule'] ?? '' }}
                  </a>
                </td>
                <td>{{ number_format((float)($t['poids'] ?? 0), 0, ',', ' ') }}</td>
                <td>{{ number_format((float)($t['montant_paie'] ?? 0), 0, ',', ' ') }}</td>
                <td>
                  @if($t['fiche_id'])
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPrixTransport{{ $t['id_ticket'] }}">
                      <i class="bx bx-edit"></i>
                    </button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
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

    @if($ticketCount > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Resume</h5>
          @php
            $totalMontant = array_sum(array_column($ticketsArray, 'montant_paie'));
            $totalPaye = array_sum(array_column($ticketsArray, 'montant_payer'));
            $totalReste = array_sum(array_column($ticketsArray, 'montant_reste'));
          @endphp
          <div class="row">
            <div class="col-md-4">
              <p><strong>Total montant:</strong> {{ number_format($totalMontant, 0, ',', ' ') }} FCFA</p>
            </div>
            <div class="col-md-4">
              <p><strong>Total paye:</strong> <span class="text-success">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</span></p>
            </div>
            <div class="col-md-4">
              <p><strong>Total reste:</strong> <span class="text-danger">{{ number_format($totalReste, 0, ',', ' ') }} FCFA</span></p>
            </div>
          </div>
        </div>
      </div>
    @endif

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
                  <div class="mb-2"><strong>Prix unitaire transport:</strong> {{ $prixTransportModal > 0 ? number_format($prixTransportModal, 0, ',', ' ') : '-' }}</div>
                  <div class="mb-2"><strong>Poids unitaire régime:</strong> {{ $poidsRegimeModal > 0 ? number_format($poidsRegimeModal, 0, ',', ' ') : '-' }}</div>
                  <div class="mb-2"><strong>Montant transport:</strong> {{ $montantTransportModal !== null ? number_format($montantTransportModal, 0, ',', ' ') . ' FCFA' : '-' }}</div>
                  <div class="mb-2"><strong>Montant régime:</strong> {{ $montantRegimeModal !== null ? number_format($montantRegimeModal, 0, ',', ' ') . ' FCFA' : '-' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
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
@endsection
