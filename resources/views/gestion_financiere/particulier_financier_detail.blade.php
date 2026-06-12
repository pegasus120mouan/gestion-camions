@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière — {{ $agent->nom_complet }}</h4>
        @if($agent->numero_agent)
          <span class="badge bg-label-primary me-1">{{ $agent->numero_agent }}</span>
        @endif
        @if($agent->groupe)
          <span class="badge bg-label-info">{{ $agent->groupe->nom_groupe }}</span>
        @endif
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('particuliers.prix.show', $agent) }}" class="btn btn-outline-primary btn-sm">
          <i class="bx bx-money me-1"></i>Prix unitaires
        </a>
        <a href="{{ route('gestionfinanciere.montant_particulier', $queryFiltres ?? []) }}" class="btn btn-secondary">
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

    @include('gestion_financiere._filtres_montant_agent', [
      'actionRoute' => route('gestionfinanciere.particulier.show', $agent),
      'filtres' => $filtres,
      'filtresActifs' => $filtresActifs,
      'produits' => $produits,
      'usines' => $usines,
      'showSyntheseLink' => false,
    ])

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">Montant dû @if($filtresActifs)<small>(filtre)</small>@endif</h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
            @if($filtresActifs)
              <small class="text-muted">Total agent : {{ number_format($montantDuGlobal, 0, ',', ' ') }} FCFA</small>
            @else
              <small class="text-muted">Tickets (tarifs particuliers / usine / type camion)</small>
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
          <div class="card-body">
            <h6 class="card-title" style="color: #0f5132;">Montant payé</h6>
            <h3 class="mb-0" style="color: #0f5132;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
          <div class="card-body">
            <h6 class="card-title" style="color: #664d03;">Reste à payer</h6>
            <h3 class="mb-0" style="color: #664d03;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Détail des tickets ({{ count($ticketsAvecMontant) }})</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>N° Ticket</th>
              <th>Véhicule</th>
              <th>Produit</th>
              <th>Usine</th>
              <th class="text-end">Poids</th>
              <th class="text-end">PU</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($groupesProduitUsine as $groupe)
              @foreach($groupe['usines'] as $blocUsine)
                @foreach($blocUsine['lignes'] as $item)
                  @php $ticket = $item['ticket']; @endphp
                  <tr>
                    <td>{{ $ticket->date_ticket ? $ticket->date_ticket->format('d/m/Y') : '-' }}</td>
                    <td>{{ $ticket->numero_ticket }}</td>
                    <td>{{ $ticket->matricule_vehicule ?? '-' }}</td>
                    <td><span class="badge bg-label-info">{{ $item['nom_produit'] }}</span></td>
                    <td><small>{{ $item['nom_usine'] }}</small></td>
                    <td class="text-end">{{ $ticket->poids ? number_format((float) $ticket->poids, 0, ',', ' ') : '—' }}</td>
                    <td class="text-end">{{ $item['prix_unitaire'] !== null ? number_format($item['prix_unitaire'], 0, ',', ' ') : '—' }}</td>
                    <td class="text-end text-danger">{{ $item['montant'] > 0 ? number_format($item['montant'], 0, ',', ' ') . ' FCFA' : '—' }}</td>
                  </tr>
                @endforeach
              @endforeach
            @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Aucun ticket pour ces critères</td></tr>
            @endforelse
          </tbody>
          @if(count($ticketsAvecMontant) > 0)
            <tfoot>
              <tr class="table-danger">
                <td colspan="7" class="text-end"><strong>Total affiché</strong></td>
                <td class="text-end"><strong>{{ number_format($montantDu, 0, ',', ' ') }} FCFA</strong></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #d1e7dd;">
        <h5 class="mb-0"><i class="bx bx-plus-circle me-2"></i>Paiements ({{ $paiements->count() }})</h5>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiementParticulierDetail">
          <i class="bx bx-plus"></i> Ajouter
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr><th>Date</th><th>Mode</th><th class="text-end">Montant</th></tr>
          </thead>
          <tbody>
            @forelse($paiements as $paiement)
              <tr>
                <td>{{ $paiement->date_paiement?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $paiement->mode_paiement ? $paiement->mode_paiement : '-' }}</td>
                <td class="text-end text-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center">Aucun paiement</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPaiementParticulierDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('gestionfinanciere.paiement_particulier.store', $agent) }}" method="POST">
        @csrf
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Nouveau paiement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">Reste à payer : <strong>{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</strong></div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="number" name="montant" class="form-control" required min="1" />
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date_paiement" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Mode</label>
            <select name="mode_paiement" class="form-select">
              <option value="">--</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@include('gestion_financiere._filtres_montant_agent_js')
@endsection
