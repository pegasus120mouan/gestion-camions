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
              <th>Statut</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($tickets as $t)
              <tr>
                <td><strong>{{ $t['numero_ticket'] ?? '-' }}</strong></td>
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
                <td colspan="8" class="text-center">
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

    @if(count($tickets) > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Résumé</h5>
          @php
            $totalPoids = array_sum(array_column($tickets, 'poids'));
            $totalMontant = 0;
            foreach ($tickets as $t) {
              $totalMontant += ((float)($t['poids'] ?? 0) * (float)($t['prix_unitaire'] ?? 0));
            }
          @endphp
          <p><strong>Nombre de tickets:</strong> <span class="badge bg-primary">{{ count($tickets) }}</span></p>
          <p><strong>Poids total:</strong> <span class="text-info">{{ number_format($totalPoids, 0, ',', ' ') }} kg</span></p>
          <p><strong>Montant total:</strong> <span class="text-success">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</span></p>
        </div>
      </div>
    @endif

    @if($pagination['last_page'] > 1)
      <div class="mt-4 d-flex justify-content-center">
        <nav>
          <ul class="pagination">
            @if($pagination['current_page'] > 1)
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $pagination['current_page'] - 1]) }}">
                  <i class="bx bx-chevron-left"></i>
                </a>
              </li>
            @endif

            @for($i = 1; $i <= $pagination['last_page']; $i++)
              <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $i]) }}">{{ $i }}</a>
              </li>
            @endfor

            @if($pagination['current_page'] < $pagination['last_page'])
              <li class="page-item">
                <a class="page-link" href="{{ route('tickets.unipalm', ['page' => $pagination['current_page'] + 1]) }}">
                  <i class="bx bx-chevron-right"></i>
                </a>
              </li>
            @endif
          </ul>
        </nav>
      </div>
    @endif
  </div>
</div>
@endsection
