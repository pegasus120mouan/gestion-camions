@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Tickets Unipalm - Camions PGF</h4>
    </div>

    @if(!$groupe_pgf)
      <div class="alert alert-warning">
        <i class="bx bx-info-circle me-2"></i>
        Le groupe PGF n'existe pas encore. Veuillez d'abord <a href="{{ route('camions.camions_pgf') }}">ajouter des camions au groupe PGF</a>.
      </div>
    @endif

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Liste des tickets</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>N° Ticket</th>
              <th>Date</th>
              <th>Véhicule</th>
              <th>Agent</th>
              <th>Usine</th>
              <th>Poids (kg)</th>
              <th>Prix unitaire</th>
              <th>Fiche</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($tickets as $t)
              <tr>
                <td>
                  <a href="#" class="text-primary fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalDetailTicket{{ $loop->index }}">
                    {{ $t['numero_ticket'] ?? '-' }}
                  </a>
                </td>
                <td>
                  @if(!empty($t['date_ticket']))
                    {{ \Carbon\Carbon::parse($t['date_ticket'])->format('d/m/Y') }}
                  @else
                    -
                  @endif
                </td>
                <td>
                  <span class="badge bg-primary">{{ $t['matricule_vehicule'] ?? '-' }}</span>
                </td>
                <td>
                  @php
                    $agentNom = trim(($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? ''));
                  @endphp
                  {{ $agentNom ?: '-' }}
                </td>
                <td>{{ $t['nom_usine'] ?? '-' }}</td>
                <td>{{ number_format((float)($t['poids'] ?? 0), 0, ',', ' ') }}</td>
                <td>{{ number_format((float)($t['prix_unitaire'] ?? 0), 0, ',', ' ') }} FCFA</td>
                <td>
                  @if($t['has_fiche'] ?? false)
                    <span class="badge bg-success"><i class="bx bx-check"></i> Associé</span>
                  @else
                    <span class="badge bg-secondary">Non associé</span>
                  @endif
                </td>
                <td>
                  @php $statut = $t['statut_ticket'] ?? ''; @endphp
                  @if($statut === 'valide')
                    <span class="badge bg-success">Validé</span>
                  @elseif($statut === 'en_attente')
                    <span class="badge bg-warning">En attente</span>
                  @elseif($statut === 'annule')
                    <span class="badge bg-danger">Annulé</span>
                  @else
                    <span class="badge bg-secondary">{{ $statut ?: '-' }}</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center">
                  @if($groupe_pgf)
                    Aucun ticket trouvé pour les camions du groupe PGF
                  @else
                    Veuillez d'abord configurer le groupe PGF
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($pagination['last_page'] > 1)
      @php
        $currentPage = $pagination['current_page'];
        $lastPage = $pagination['last_page'];
        $range = 2;
        $startPage = max(1, $currentPage - $range);
        $endPage = min($lastPage, $currentPage + $range);
      @endphp
      <div class="mt-4 d-flex justify-content-center">
        <nav>
          <ul class="pagination">
            {{-- Première page --}}
            @if($currentPage > 1)
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => 1]) }}">
                  <i class="bx bx-chevrons-left"></i>
                </a>
              </li>
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $currentPage - 1]) }}">
                  <i class="bx bx-chevron-left"></i>
                </a>
              </li>
            @endif

            {{-- Pages avec ellipsis --}}
            @if($startPage > 1)
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => 1]) }}">1</a>
              </li>
              @if($startPage > 2)
                <li class="page-item disabled"><span class="page-link">...</span></li>
              @endif
            @endif

            @for($i = $startPage; $i <= $endPage; $i++)
              <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $i]) }}">{{ $i }}</a>
              </li>
            @endfor

            @if($endPage < $lastPage)
              @if($endPage < $lastPage - 1)
                <li class="page-item disabled"><span class="page-link">...</span></li>
              @endif
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $lastPage]) }}">{{ $lastPage }}</a>
              </li>
            @endif

            {{-- Dernière page --}}
            @if($currentPage < $lastPage)
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $currentPage + 1]) }}">
                  <i class="bx bx-chevron-right"></i>
                </a>
              </li>
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $lastPage]) }}">
                  <i class="bx bx-chevrons-right"></i>
                </a>
              </li>
            @endif
          </ul>
        </nav>
      </div>
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
  $agentNomModal = trim(($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? ''));
  $poidsUsineModal = (float)($t['poids'] ?? 0);
  $poidsParc = (float)($t['poids_parc'] ?? 0);
  $poidsEcartModal = $poidsParc > 0 ? $poidsUsineModal - $poidsParc : null;
  $prixUnitaire = (float)($t['prix_unitaire'] ?? 0);
  $montantPaye = (float)($t['montant_paie'] ?? 0);
  $dateChargement = $t['date_chargement'] ?? '-';
  $origine = $t['origine'] ?? '-';
@endphp
<div class="modal fade" id="modalDetailTicket{{ $index }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-receipt me-2"></i>Ticket {{ $t['numero_ticket'] ?? '' }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Informations générales -->
          <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
              <div class="card-body">
                <h6 class="card-title text-warning mb-3"><i class="bx bx-info-circle me-1"></i>Informations générales</h6>
                <div class="mb-2"><strong>N° Ticket:</strong> {{ $t['numero_ticket'] ?? '-' }}</div>
                <div class="mb-2"><strong>Véhicule:</strong> {{ $t['matricule_vehicule'] ?? '-' }}</div>
                <div class="mb-2"><strong>Transporteur:</strong> <span class="badge bg-info">Autre</span></div>
                <div class="mb-2"><strong>Usine:</strong> {{ $t['nom_usine'] ?? '-' }}</div>
                <div class="mb-2"><strong>Agent:</strong> {{ $agentNomModal ?: '-' }}</div>
                <div class="mb-2"><strong>Origine:</strong> {{ $origine }}</div>
              </div>
            </div>
          </div>
          <!-- Dates -->
          <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
              <div class="card-body">
                <h6 class="card-title text-primary mb-3"><i class="bx bx-calendar me-1"></i>Dates</h6>
                <div class="mb-2"><strong>Date chargement:</strong> {{ $dateChargement }}</div>
                <div class="mb-2"><strong>Date déchargement:</strong> {{ $dateTicketModal ?: '-' }}</div>
                <div class="mb-2"><strong>Date d'ajout:</strong> {{ isset($t['created_at']) ? \Carbon\Carbon::parse($t['created_at'])->format('d-m-Y H:i') : '-' }}</div>
              </div>
            </div>
          </div>
          <!-- Poids -->
          <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
              <div class="card-body">
                <h6 class="card-title text-success mb-3"><i class="bx bx-package me-1"></i>Poids</h6>
                <div class="mb-2"><strong>Poids sur Parc:</strong> {{ $poidsParc > 0 ? number_format($poidsParc, 0, ',', ' ') . ' kg' : '-' }}</div>
                <div class="mb-2"><strong>Poids Usine:</strong> {{ number_format($poidsUsineModal, 0, ',', ' ') }} kg</div>
                <div class="mb-2"><strong>Poids Ecart:</strong> {{ $poidsEcartModal !== null ? number_format($poidsEcartModal, 0, ',', ' ') . ' kg' : '-' }}</div>
              </div>
            </div>
          </div>
          <!-- Montants -->
          <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
              <div class="card-body">
                <h6 class="card-title text-danger mb-3"><i class="bx bx-money me-1"></i>Montants</h6>
                <div class="mb-2"><strong>Prix unitaire:</strong> {{ number_format($prixUnitaire, 0, ',', ' ') }} FCFA</div>
                <div class="mb-2"><strong>Montant payé:</strong> {{ number_format($montantPaye, 0, ',', ' ') }} FCFA</div>
                <div class="mb-2"><strong>Prix unitaire Agent:</strong> -</div>
                <div class="mb-2"><strong>Montant Agents:</strong> -</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success"><i class="bx bx-check me-1"></i>Vérifier avec Unipalm</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
@endforeach

<!-- Modals pour associer une fiche de sortie -->
@foreach($tickets as $index => $t)
<div class="modal fade" id="modalAssocierFiche{{ $index }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-link me-2"></i>Associer à une fiche de sortie</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('tickets.associer_fiche') }}" method="POST">
        @csrf
        <input type="hidden" name="id_ticket" value="{{ $t['id_ticket'] ?? '' }}" />
        <input type="hidden" name="numero_ticket" value="{{ $t['numero_ticket'] ?? '' }}" />
        <div class="modal-body">
          <div class="alert alert-info mb-3">
            <strong>Ticket:</strong> {{ $t['numero_ticket'] ?? '-' }}<br>
            <strong>Véhicule:</strong> {{ $t['matricule_vehicule'] ?? '-' }}<br>
            <strong>Agent:</strong> {{ trim(($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? '')) ?: '-' }}
          </div>
          <div class="mb-3">
            <label class="form-label">Sélectionner une fiche de sortie</label>
            <select name="fiche_id" class="form-select" required>
              <option value="">-- Sélectionner une fiche --</option>
              @foreach($fiches_disponibles ?? [] as $fiche)
                <option value="{{ $fiche->id }}">
                  {{ $fiche->matricule_vehicule }} - {{ $fiche->nom_pont }} ({{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }})
                </option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success"><i class="bx bx-link me-1"></i>Associer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach
@endsection
