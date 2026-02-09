@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">
        <i class="bx bx-export text-primary me-2"></i>
        Sorties de stock par pont
      </h4>
    </div>

    @if(!empty($external_error))
      <div class="alert alert-danger">{{ $external_error }}</div>
    @endif

    @if(count($sortiesParPont) === 0)
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bx bx-package text-muted" style="font-size: 4rem;"></i>
          <p class="text-muted mt-3 mb-0">Aucune sortie de stock à afficher</p>
          <small class="text-muted">Les sorties sont calculées à partir des tickets associés aux fiches de sortie</small>
        </div>
      </div>
    @else
      @foreach($sortiesParPont as $sortie)
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
            <div class="text-white">
              <h5 class="mb-0 text-white">
                <i class="bx bx-map me-2"></i>{{ $sortie['nom_pont'] ?? 'Pont inconnu' }}
              </h5>
              <small>{{ $sortie['nb_tickets'] }} ticket(s) associé(s)</small>
            </div>
            <div class="text-end text-white">
              <div class="d-flex gap-4">
                <div>
                  <small>Stock actuel</small>
                  <h5 class="mb-0 text-white">{{ number_format($sortie['stock_actuel'] ?? 0, 0, ',', ' ') }} kg</h5>
                </div>
                <div>
                  <small>Total sorties (Poids Usine)</small>
                  <h5 class="mb-0 text-warning">- {{ number_format($sortie['total_poids_usine'] ?? 0, 0, ',', ' ') }} kg</h5>
                </div>
                <div>
                  <small>Stock après sortie</small>
                  <h5 class="mb-0 {{ ($sortie['stock_apres_sortie'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($sortie['stock_apres_sortie'] ?? 0, 0, ',', ' ') }} kg
                  </h5>
                </div>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>N° Ticket</th>
                  <th>Véhicule</th>
                  <th>Date déchargement</th>
                  <th class="text-end">Poids Usine (kg)</th>
                </tr>
              </thead>
              <tbody>
                @foreach($sortie['tickets'] as $ticket)
                  <tr>
                    <td><strong>{{ $ticket['numero_ticket'] }}</strong></td>
                    <td>{{ $ticket['matricule_vehicule'] }}</td>
                    <td>
                      @if($ticket['date_dechargement'])
                        {{ \Carbon\Carbon::parse($ticket['date_dechargement'])->format('d-m-Y') }}
                      @else
                        -
                      @endif
                    </td>
                    <td class="text-end">
                      <span class="badge bg-warning">{{ number_format($ticket['poids_usine'], 0, ',', ' ') }} kg</span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr class="table-light">
                  <td colspan="3" class="text-end"><strong>Total Poids Usine:</strong></td>
                  <td class="text-end">
                    <strong class="text-danger">{{ number_format($sortie['total_poids_usine'], 0, ',', ' ') }} kg</strong>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      @endforeach
    @endif

  </div>
</div>
@endsection
