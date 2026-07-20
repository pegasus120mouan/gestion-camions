@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière — PGF</h4>
        <span class="badge bg-label-primary">PGF</span>
        <span class="badge bg-secondary ms-1">{{ $camionsCount }} camion(s)</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('camions.activites') }}" class="btn btn-outline-info btn-sm">
          <i class="bx bx-list-ul me-1"></i>Activités
        </a>
        <a href="{{ route('camions.revenues') }}" class="btn btn-secondary">
          <i class="bx bx-arrow-back me-1"></i>Retour
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Filtres</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('camions.revenues.show') }}" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Véhicule</label>
            <select name="vehicule" class="form-select">
              <option value="">Tous les véhicules</option>
              @foreach($vehiculesPgf as $vehicule)
                <option value="{{ $vehicule->matricule_vehicule }}" @selected(($filtres['vehicule'] ?? '') === $vehicule->matricule_vehicule)>
                  {{ $vehicule->matricule_vehicule }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" value="{{ $filtres['date_debut'] ?? '' }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="{{ $filtres['date_fin'] ?? '' }}">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-search me-1"></i>Filtrer
            </button>
            <a href="{{ route('camions.revenues.show') }}" class="btn btn-outline-secondary">
              <i class="bx bx-reset me-1"></i>Réinitialiser
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">Montant dû</h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Revenus camions PGF (fiches / tickets)</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
          <div class="card-body">
            <h6 class="card-title" style="color: #0f5132;">Montant payé</h6>
            <h3 class="mb-0" style="color: #0f5132;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Paiements enregistrés</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
          <div class="card-body">
            <h6 class="card-title" style="color: #664d03;">Reste à payer</h6>
            <h3 class="mb-0" style="color: #664d03;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Montant dû − montant payé</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
        <h5 class="card-title mb-0" style="color: #842029;">
          <i class="bx bx-bus me-2"></i>Camions PGF
        </h5>
        <a href="{{ route('camions.camions_pgf') }}" class="btn btn-sm btn-outline-primary">
          <i class="bx bx-list-ul me-1"></i>Liste camions
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Matricule</th>
              <th class="text-center">Fiches</th>
              <th class="text-end">Montant dû</th>
              <th class="text-end">Montant payé</th>
              <th class="text-end">Reste à payer</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($camionsDetails as $camion)
              <tr>
                <td class="fw-semibold">{{ $camion['matricule'] ?: ('#'.$camion['vehicule_id']) }}</td>
                <td class="text-center">{{ $camion['nb_fiches'] }}</td>
                <td class="text-end text-primary fw-bold">{{ number_format($camion['montant_du'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-success">{{ number_format($camion['montant_paye'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end">
                  @if($camion['reste_a_payer'] > 0)
                    <span class="text-danger fw-bold">{{ number_format($camion['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @elseif($camion['reste_a_payer'] < 0)
                    <span class="text-warning fw-bold">{{ number_format($camion['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-success"><i class="bx bx-check-circle"></i> Soldé</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('bilan-vehicule.show', $camion['vehicule_id']) }}" class="btn btn-sm btn-outline-primary" title="Bilan">
                    <i class="bx bx-show"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Aucun camion PGF.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-file me-2"></i>Détail des fiches ({{ $fiches->count() }})</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Véhicule</th>
              <th>N° fiche</th>
              <th class="text-end">Poids</th>
              <th class="text-end">PU camion</th>
              <th class="text-end">Montant</th>
              <th class="text-end">Payé</th>
            </tr>
          </thead>
          <tbody>
            @forelse($fiches as $fiche)
              <tr>
                <td>{{ $fiche->date_chargement?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $fiche->matricule_vehicule ?: '—' }}</td>
                <td>
                  <span class="badge bg-label-success">{{ $fiche->numero_fiche ?: ('#'.$fiche->id) }}</span>
                </td>
                <td class="text-end">{{ number_format((float) ($fiche->poids_pont ?? 0), 0, ',', ' ') }}</td>
                <td class="text-end">{{ number_format((float) ($fiche->prix_unitaire_camion ?? 0), 0, ',', ' ') }}</td>
                <td class="text-end text-danger fw-bold">{{ number_format((float) ($fiche->montant_camion ?? 0), 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-success">{{ number_format((float) ($fiche->montant_paye_transporteur ?? 0), 0, ',', ' ') }} FCFA</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Aucune fiche pour ces filtres.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-ticket me-2"></i>Numéros de tickets usine camion ({{ $tickets->count() }})</h5>
        <a href="{{ route('camions.activites') }}" class="btn btn-sm btn-outline-primary">
          <i class="bx bx-list-ul me-1"></i>Voir activités
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date ticket</th>
              <th>N° Ticket</th>
              <th>Usine</th>
              <th>Agent</th>
              <th>Pont</th>
              <th>Véhicule</th>
              <th class="text-end">Poids Usine</th>
              <th class="text-end">Prix unitaire</th>
              <th class="text-end">Montant</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $ticket)
              <tr>
                <td>{{ $ticket['date_ticket']?->format('d-m-Y') ?? '—' }}</td>
                <td>
                  <a href="{{ route('camions.activites', ['q' => $ticket['numero_ticket']]) }}" class="text-primary fw-semibold">
                    {{ $ticket['numero_ticket'] ?: '—' }}
                  </a>
                </td>
                <td>{{ $ticket['nom_usine'] }}</td>
                <td>{{ $ticket['nom_agent'] }}</td>
                <td>{{ $ticket['nom_pont'] }}</td>
                <td>
                  @if(!empty($ticket['vehicule_id']))
                    <a href="{{ route('vehicules.depenses', ['vehicule_id' => $ticket['vehicule_id'], 'matricule' => $ticket['matricule_vehicule']]) }}" class="text-primary">
                      {{ $ticket['matricule_vehicule'] }}
                    </a>
                  @else
                    {{ $ticket['matricule_vehicule'] }}
                  @endif
                </td>
                <td class="text-end">{{ number_format((float) $ticket['poids'], 0, ',', ' ') }}</td>
                <td class="text-end">
                  @if($ticket['prix_unitaire'] !== null)
                    <span class="badge bg-label-primary">{{ rtrim(rtrim(number_format((float) $ticket['prix_unitaire'], 2, '.', ''), '0'), '.') }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end fw-semibold">
                  {{ $ticket['montant'] !== null ? number_format((float) $ticket['montant'], 0, ',', ' ').' FCFA' : '—' }}
                </td>
                <td>
                  @if($ticket['est_paye'])
                    <span class="badge bg-label-success">Payé</span>
                  @else
                    <span class="badge bg-label-warning">Non payé</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">Aucun ticket usine pour ces filtres.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
