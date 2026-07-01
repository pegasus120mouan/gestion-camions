@extends('layout.main')

@section('title', 'Détails Véhicule - ' . $matricule)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      <!-- Header avec infos véhicule -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-3 bg-label-primary">
                <span class="avatar-initial rounded-circle"><i class="bx bx-car fs-3"></i></span>
              </div>
              <div>
                <h4 class="mb-0 fw-bold">{{ $matricule }}</h4>
                <span class="text-muted">
                  Transporteur :
                  @if(!empty($transporteur))
                    {{ $transporteur->nom }} {{ $transporteur->prenoms }} ({{ $transporteur->code }})
                  @else
                    Non assigné
                  @endif
                </span>
              </div>
            </div>
            @if(!empty($transporteur))
              <a href="{{ route('gestionfinanciere.transporteur.show', $transporteur) }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Retour au transporteur
              </a>
            @else
              <a href="{{ route('gestionfinanciere.montant_transporteur') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Retour à la liste
              </a>
            @endif
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Synthèse -->
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">SYNTHÈSE</h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-3">
                <span>Fiches de sortie</span>
                <span class="fw-bold">{{ $totalFiches }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Paiements effectués</span>
                <span class="fw-bold">{{ $paiements->count() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Synthèse Financière -->
        <div class="col-md-8 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">SYNTHÈSE FINANCIÈRE</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4 mb-3">
                  <div class="border rounded p-3">
                    <div class="text-muted small">Montant Global</div>
                    <div class="d-flex align-items-center">
                      <i class="bx bx-money text-danger me-2"></i>
                      <span class="text-danger fw-bold">{{ number_format($montantGlobal, 0, ',', ' ') }} FCFA</span>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="border rounded p-3">
                    <div class="text-muted small">Montant Payé</div>
                    <div class="d-flex align-items-center">
                      <i class="bx bx-check-circle text-success me-2"></i>
                      <span class="text-success fw-bold">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</span>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="border rounded p-3 {{ $resteAPayer < 0 ? 'bg-danger' : 'bg-success' }} text-white">
                    <div class="small">Reste à Payer</div>
                    <div class="d-flex align-items-center">
                      <i class="bx bx-wallet me-2"></i>
                      <span class="fw-bold">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <div class="border rounded p-3">
                    <div class="text-muted small">Avance (Carburant + Frais + Dépenses)</div>
                    <div class="d-flex align-items-center">
                      <i class="bx bx-gas-pump text-info me-2"></i>
                      <span class="text-info fw-bold">{{ number_format($totalAvance, 0, ',', ' ') }} FCFA</span>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <div class="border rounded p-3">
                    <div class="text-muted small">Paiements directs</div>
                    <div class="d-flex align-items-center">
                      <i class="bx bx-credit-card text-primary me-2"></i>
                      <span class="text-primary fw-bold">{{ number_format($montantPaye - $totalAvance, 0, ',', ' ') }} FCFA</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des fiches de sortie -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bx bx-file me-2"></i> Fiches de sortie ({{ $totalFiches }})</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Pont</th>
                <th>Usine</th>
                <th>Poids (kg)</th>
                <th>PU</th>
                <th>Montant Global</th>
                <th>Avance</th>
                <th>Payé</th>
                <th>Reste</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($fichesSortie as $fiche)
                @php
                  $poids = $fiche->poids_pont ?? 0;
                  $pu = $fiche->prix_unitaire_transport ?? 0;
                  $montantGlobalFiche = $poids * $pu;
                  
                  $depensesFiche = \App\Models\Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
                      ->whereDate('date_depense', '>=', $fiche->date_chargement)
                      ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
                      ->sum('montant');
                  $avanceFiche = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0) + $depensesFiche;
                  
                  $payeFiche = $fiche->montant_paye_transporteur ?? 0;
                  $resteFiche = $montantGlobalFiche - $avanceFiche - $payeFiche;
                @endphp
                <tr>
                  <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</td>
                  <td>{{ $fiche->nom_pont ?? '-' }}</td>
                  <td>{{ $fiche->usine ?? '-' }}</td>
                  <td>{{ $poids ? number_format($poids, 0, ',', ' ') : '-' }}</td>
                  <td>
                    @if($pu > 0)
                      <span class="fw-semibold text-primary">{{ number_format($pu, 0, ',', ' ') }} FCFA</span>
                    @else
                      <span class="badge bg-label-warning">Non saisi</span>
                    @endif
                  </td>
                  <td class="text-danger fw-bold">{{ $montantGlobalFiche > 0 ? number_format($montantGlobalFiche, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                  <td class="text-info">{{ number_format($avanceFiche, 0, ',', ' ') }} FCFA</td>
                  <td class="text-success">{{ number_format($payeFiche, 0, ',', ' ') }} FCFA</td>
                  <td class="{{ $resteFiche < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ number_format($resteFiche, 0, ',', ' ') }} FCFA</td>
                  <td>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalPU{{ $fiche->id }}">
                      <i class="bx bx-money me-1"></i>PU
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="text-center text-muted">Aucune fiche de sortie</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      @foreach($fichesSortie as $fiche)
        @php
          $poidsModal = $fiche->poids_pont ?? 0;
          $puModal = $fiche->prix_unitaire_transport;
        @endphp
        <div class="modal fade" id="modalPU{{ $fiche->id }}" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form action="{{ route('gestionfinanciere.transporteur.updatePU', $fiche->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Prix unitaire</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Prix unitaire (FCFA / kg) <span class="text-danger">*</span></label>
                    <input type="number" name="prix_unitaire" class="form-control" value="{{ $puModal ?? '' }}" min="0" step="1" required>
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
      @endforeach

      <!-- Historique des paiements -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="bx bx-history me-2"></i> Historique des paiements ({{ $paiements->count() }})</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Montant</th>
                <th>Observation</th>
              </tr>
            </thead>
            <tbody>
              @forelse($paiements as $paiement)
                <tr>
                  <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}</td>
                  <td class="text-success fw-bold">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                  <td>{{ $paiement->observation ?? '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted">Aucun paiement enregistré</td>
                </tr>
              @endforelse
            </tbody>
            @if($paiements->count() > 0)
              <tfoot class="table-light">
                <tr class="fw-bold">
                  <td>Total</td>
                  <td class="text-success">{{ number_format($paiements->sum('montant'), 0, ',', ' ') }} FCFA</td>
                  <td></td>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
