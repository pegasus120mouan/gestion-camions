@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    @php
      $nomComplet = $agent['nom_complet'] ?? trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? ''));
      $idAgent = (int) ($agent['id_agent'] ?? 0);
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière — {{ $nomComplet ?: 'Agent' }}</h4>
        @if(!empty($agent['numero_agent']))
          <span class="badge bg-label-primary me-1">{{ $agent['numero_agent'] }}</span>
        @endif
        @if(!empty($agent['contact']))
          <span class="badge bg-secondary">{{ $agent['contact'] }}</span>
        @endif
      </div>
      <div>
        <a href="{{ route('gestionfinanciere.montant_agent') }}" class="btn btn-secondary">
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

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">Montant dû</h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Montants enregistrés au déchargement (tarif selon usine et type de camion : Pisteur, PGF ou autre)</small>
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

    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
            <h5 class="card-title mb-0" style="color: #842029;"><i class="bx bx-file me-2"></i>Fiches déchargées ({{ count($fichesAvecMontant) }})</h5>
          </div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Véhicule</th>
                  <th>Usine</th>
                  <th class="text-end">PU</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($fichesAvecMontant as $item)
                  <tr>
                    <td>{{ $item['fiche']->date_chargement ? $item['fiche']->date_chargement->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item['fiche']->matricule_vehicule ?? '-' }}</td>
                    <td><small>{{ $item['fiche']->usine ?? '-' }}</small></td>
                    <td class="text-end">
                      @if($item['prix_unitaire'] !== null)
                        {{ number_format($item['prix_unitaire'], 0, ',', ' ') }}
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-end text-danger">{{ $item['montant'] > 0 ? number_format($item['montant'], 0, ',', ' ') . ' FCFA' : '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center">Aucune fiche</td>
                  </tr>
                @endforelse
              </tbody>
              @if(count($fichesAvecMontant) > 0)
                <tfoot>
                  <tr class="table-warning">
                    <td colspan="4"><strong>Total dû (lignes calculées)</strong></td>
                    <td class="text-end"><strong>{{ number_format($montantDu, 0, ',', ' ') }} FCFA</strong></td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #d1e7dd; border-bottom: 1px solid #badbcc;">
            <h5 class="card-title mb-0" style="color: #0f5132;"><i class="bx bx-plus-circle me-2"></i>Paiements ({{ $paiements->count() }})</h5>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiementAgentDetail">
              <i class="bx bx-plus"></i> Ajouter
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Mode</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($paiements as $paiement)
                  <tr>
                    <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}</td>
                    <td>
                      @if($paiement->mode_paiement)
                        <span class="badge bg-info">{{ $paiement->mode_paiement }}</span>
                      @else
                        -
                      @endif
                    </td>
                    <td class="text-end text-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center">Aucun paiement</td>
                  </tr>
                @endforelse
              </tbody>
              @if($paiements->count() > 0)
                <tfoot>
                  <tr class="table-success">
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-end"><strong>{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</strong></td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPaiementAgentDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Nouveau paiement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('gestionfinanciere.paiement_agent.store', ['id_agent' => $idAgent]) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Reste à payer:</strong> {{ number_format($resteAPayer, 0, ',', ' ') }} FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="number" name="montant" class="form-control" required min="1" step="1" />
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" placeholder="Optionnel" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
