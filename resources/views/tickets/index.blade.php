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
              <th>Vehicule</th>
              <th>Usine</th>
              <th>Agent</th>
              <th>Poids (kg)</th>
              <th>Prix unitaire</th>
              <th>Montant</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($tickets as $t)
              <tr>
                <td>{{ $t['numero_ticket'] ?? '' }}</td>
                <td>
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $t['vehicule_id'] ?? 0, 'matricule' => $t['matricule_vehicule'] ?? '']) }}">
                    {{ $t['matricule_vehicule'] ?? '' }}
                  </a>
                </td>
                <td>{{ $t['nom_usine'] ?? '-' }}</td>
                <td>{{ ($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? '') }}</td>
                <td>{{ number_format((float)($t['poids'] ?? 0), 0, ',', ' ') }}</td>
                <td>{{ number_format((float)($t['prix_unitaire'] ?? 0), 0, ',', ' ') }}</td>
                <td>{{ number_format((float)($t['montant_paie'] ?? 0), 0, ',', ' ') }}</td>
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
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center">Aucun ticket</td>
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
@endsection
