@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('groupes.show', $groupe->id) }}" class="text-primary mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i>Retour au groupe
        </a>
        <h4 class="mb-0"><i class="bx bx-receipt text-primary me-2"></i>Tickets du groupe {{ $groupe->nom_groupe }}</h4>
      </div>
    </div>

    <!-- Résumé -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Nombre de tickets</h6>
                <h3 class="mb-0">{{ count($tickets) }}</h3>
              </div>
              <i class="bx bx-receipt" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Poids total (kg)</h6>
                @php
                  $poidsTotal = array_sum(array_map(fn($t) => (float)($t['poids'] ?? 0), $tickets));
                @endphp
                <h3 class="mb-0">{{ number_format($poidsTotal, 0, ',', ' ') }}</h3>
              </div>
              <i class="bx bx-package" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Agents du groupe</h6>
                <h3 class="mb-0">{{ $groupe->agents->count() }}</h3>
              </div>
              <i class="bx bx-user" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Liste des tickets -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Liste des tickets</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>N° Ticket</th>
              <th>Date Ticket</th>
              <th>Véhicule</th>
              <th>Agent</th>
              <th>Pont</th>
              <th class="text-end">Poids (kg)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $ticket)
              @php
                $fiche = $fichesParTicket[$ticket['id_ticket']] ?? null;
              @endphp
              <tr>
                <td><strong>{{ $ticket['numero_ticket'] ?? '-' }}</strong></td>
                <td>{{ isset($ticket['date_ticket']) ? \Carbon\Carbon::parse($ticket['date_ticket'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $ticket['matricule_vehicule'] ?? '-' }}</td>
                <td>
                  @if($fiche)
                    <span class="badge bg-info">{{ $fiche->nom_agent ?? '-' }}</span>
                  @else
                    -
                  @endif
                </td>
                <td>
                  @if($fiche)
                    <span class="badge bg-secondary">{{ $fiche->nom_pont ?? '-' }}</span>
                  @else
                    -
                  @endif
                </td>
                <td class="text-end fw-bold">{{ number_format((float)($ticket['poids'] ?? 0), 0, ',', ' ') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Aucun ticket trouvé pour ce groupe</td>
              </tr>
            @endforelse
          </tbody>
          @if(count($tickets) > 0)
          <tfoot class="table-light">
            <tr>
              <td colspan="5" class="text-end fw-bold">Total:</td>
              <td class="text-end fw-bold text-success">{{ number_format($poidsTotal, 0, ',', ' ') }} kg</td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
