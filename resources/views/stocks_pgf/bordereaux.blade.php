@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-file text-primary me-2"></i>Bordereaux de Stock</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddBordereau">
        <i class="bx bx-plus me-1"></i>Générer un bordereau
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Numéro</th>
              <th>Date génération</th>
              <th>Période</th>
              <th>Ponts</th>
              <th class="text-end">Poids Chargé (kg)</th>
              <th class="text-end">Poids Déchargé (kg)</th>
              <th class="text-end">Ecart</th>
              <th class="text-end">Montant Ecart</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($bordereaux as $bordereau)
              <tr>
                <td>
                  <a href="{{ route('stocks_pgf.bordereau.show', $bordereau->id) }}" class="fw-bold text-primary">
                    {{ $bordereau->numero }}
                  </a>
                </td>
                <td>{{ $bordereau->date_generation ? $bordereau->date_generation->format('d-m-Y') : '-' }}</td>
                <td>
                  {{ $bordereau->date_debut ? $bordereau->date_debut->format('d-m-Y') : '-' }}
                  <i class="bx bx-right-arrow-alt text-muted"></i>
                  {{ $bordereau->date_fin ? $bordereau->date_fin->format('d-m-Y') : '-' }}
                </td>
                <td>
                  @php
                    $pontsData = $bordereau->ponts_data ?? [];
                    $nomsPonts = array_map(fn($p) => $p['nom_pont'] ?? 'Pont #' . ($p['id_pont'] ?? '-'), $pontsData);
                  @endphp
                  @foreach($nomsPonts as $nomPont)
                    <span class="badge bg-info me-1">{{ $nomPont }}</span>
                  @endforeach
                </td>
                <td class="text-end fw-bold text-success">{{ number_format($bordereau->poids_total, 0, ',', ' ') }}</td>
                <td class="text-end">{{ number_format($bordereau->poids_sortie ?? 0, 0, ',', ' ') }}</td>
                <td class="text-end">
                  @php
                    $ecart = ($bordereau->poids_sortie ?? 0) - ($bordereau->poids_total ?? 0);
                  @endphp
                  <span class="{{ $ecart >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($ecart, 0, ',', ' ') }}</span>
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-success" title="Associer tickets" 
                    data-bs-toggle="modal" data-bs-target="#modalAssocierTickets_{{ $bordereau->id }}">
                    <i class="bx bx-link"></i>
                  </button>
                  <a href="{{ route('stocks_pgf.bordereau.show', $bordereau->id) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                    <i class="bx bx-show"></i>
                  </a>
                  <form method="POST" action="{{ route('stocks_pgf.bordereau.destroy', $bordereau->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce bordereau ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Aucun bordereau généré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Générer Bordereau -->
<div class="modal fade" id="modalAddBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-file me-2"></i>Générer un bordereau de stock</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('stocks_pgf.bordereau.store') }}">
        @csrf
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début <span class="text-danger">*</span></label>
              <input type="date" name="date_debut" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin <span class="text-danger">*</span></label>
              <input type="date" name="date_fin" class="form-control" value="{{ date('Y-m-d') }}" required />
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Sélectionner les ponts <span class="text-danger">*</span></label>
            <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
              @forelse($ponts as $pont)
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="ponts[]" value="{{ $pont['id_pont'] }}" id="pont_{{ $pont['id_pont'] }}">
                  <label class="form-check-label" for="pont_{{ $pont['id_pont'] }}">
                    <strong>{{ $pont['nom_pont'] }}</strong>
                    <small class="text-muted">({{ $pont['code_pont'] }})</small>
                  </label>
                </div>
              @empty
                <p class="text-muted mb-0">Aucun pont disponible</p>
              @endforelse
            </div>
            <small class="text-muted">Cochez les ponts à inclure dans le bordereau</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Générer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals Association Tickets pour chaque bordereau -->
@foreach($bordereaux as $bordereau)
  @php
    $ticketsInfo = $ticketsParBordereau[$bordereau->id] ?? ['tickets' => [], 'total_poids' => 0];
    $ticketsEligibles = $ticketsInfo['tickets'];
    $totalPoids = $ticketsInfo['total_poids'];
  @endphp
  <div class="modal fade" id="modalAssocierTickets_{{ $bordereau->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Association des tickets au bordereau {{ $bordereau->numero }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Informations du bordereau -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <strong>Informations du bordereau</strong>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-3">
                  <strong>N° Bordereau :</strong><br>
                  <span class="text-primary">{{ $bordereau->numero }}</span>
                </div>
                <div class="col-md-3">
                  <strong>Date début :</strong><br>
                  {{ $bordereau->date_debut ? $bordereau->date_debut->format('d/m/Y') : '-' }}
                </div>
                <div class="col-md-3">
                  <strong>Date fin :</strong><br>
                  {{ $bordereau->date_fin ? $bordereau->date_fin->format('d/m/Y') : '-' }}
                </div>
                <div class="col-md-3">
                  <strong>Ponts :</strong><br>
                  @php
                    $pontsDataModal = $bordereau->ponts_data ?? [];
                    $nomsPontsModal = array_map(fn($p) => $p['nom_pont'] ?? '', $pontsDataModal);
                  @endphp
                  {{ implode(', ', $nomsPontsModal) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Liste des tickets -->
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th style="width: 40px;"></th>
                  <th>Date Réception</th>
                  <th>Date Ticket</th>
                  <th>Véhicule</th>
                  <th>N° Ticket</th>
                  <th class="text-end">Poids (kg)</th>
                </tr>
              </thead>
              <tbody>
                @forelse($ticketsEligibles as $ticket)
                  <tr>
                    <td class="text-center"><i class="bx bx-link text-success"></i></td>
                    <td>{{ $ticket['date_chargement'] ?? '-' }}</td>
                    <td>{{ $ticket['date_ticket'] ?? '-' }}</td>
                    <td>{{ $ticket['matricule_vehicule'] ?? '-' }}</td>
                    <td>{{ $ticket['numero_ticket'] ?? '-' }}</td>
                    <td class="text-end">{{ number_format($ticket['poids_usine'] ?? 0, 0, ',', ' ') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-3">Aucun ticket éligible pour ce bordereau</td>
                  </tr>
                @endforelse
              </tbody>
              @if(count($ticketsEligibles) > 0)
              <tfoot class="table-light">
                <tr>
                  <td colspan="5" class="text-end"><strong>TOTAL GÉNÉRAL ({{ count($ticketsEligibles) }} tickets)</strong></td>
                  <td class="text-end fw-bold text-success">{{ number_format($totalPoids, 0, ',', ' ') }}</td>
                </tr>
              </tfoot>
              @endif
            </table>
          </div>
        </div>
        <div class="modal-footer">
          @if(count($ticketsEligibles) > 0)
          <form method="POST" action="{{ route('stocks_pgf.bordereau.associer_tickets', $bordereau->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Confirmer l'association</button>
          </form>
          @endif
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>
@endforeach
@endsection
