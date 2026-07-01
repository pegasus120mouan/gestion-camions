@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière - {{ $transporteur->nom }} {{ $transporteur->prenoms }}</h4>
        <span class="badge bg-label-primary">{{ $transporteur->code }}</span>
        <span class="badge bg-secondary ms-1">{{ $transporteur->vehicules->count() }} camion(s)</span>
      </div>
      <div>
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalPaiementGestion">
          <i class="bx bx-plus me-1"></i>Paiement
        </button>
        <a href="{{ route('gestionfinanciere.montant_transporteur') }}" class="btn btn-secondary">
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

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">Montant dû</h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
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

    <div class="row mb-4">
      <div class="col-md-8">
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Filtres de recherche</h5>
          </div>
          <div class="card-body">
            <form method="GET" action="{{ route('gestionfinanciere.transporteur.show', $transporteur) }}">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Véhicule</label>
                  <select name="vehicule" class="form-select">
                    <option value="">Tous les véhicules</option>
                    @foreach($vehicules as $vehicule)
                      <option value="{{ $vehicule }}" {{ request('vehicule') == $vehicule ? 'selected' : '' }}>{{ $vehicule }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Date début</label>
                  <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Date fin</label>
                  <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary me-2"><i class="bx bx-search"></i></button>
                  <a href="{{ route('gestionfinanciere.transporteur.show', $transporteur) }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h5 class="mb-0">Fiches de sortie ({{ $fichesSortie->count() }})</h5>
              <small class="text-muted">Le prix unitaire (PU) est saisi manuellement pour chaque fiche.</small>
            </div>
            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalHistorique">
              <i class="bx bx-history"></i> Historique paiements fiches
            </button>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>N° ticket</th>
                  <th>Véhicule</th>
                  <th>Poids (kg)</th>
                  <th>PU</th>
                  <th class="text-end">Montant</th>
                  <th class="text-end">Avance</th>
                  <th class="text-end">Payé</th>
                  <th class="text-end">Reste</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($fichesSortie as $fiche)
                  @php
                    $poids = $ticketFicheService->poidsEffectif($fiche);
                    $numeroTicket = $ticketFicheService->numeroTicketEffectif($fiche);
                    $pu = $fiche->prix_unitaire_transport;
                    $montantGlobalFiche = $pu ? ($poids * $pu) : 0;
                    $depensesTableau = \App\Models\Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
                        ->whereDate('date_depense', '>=', $fiche->date_chargement)
                        ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
                        ->sum('montant');
                    $avanceTableau = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0) + $depensesTableau;
                    $montantPayeFiche = $fiche->montant_paye_transporteur ?? 0;
                    $resteAPayerFiche = $montantGlobalFiche - $avanceTableau - $montantPayeFiche;
                  @endphp
                  <tr>
                    <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</td>
                    <td>
                      @if($numeroTicket)
                        <span class="badge bg-label-info">{{ $numeroTicket }}</span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td>
                      <a href="{{ route('gestionfinanciere.transporteur.vehicule', ['matricule' => $fiche->matricule_vehicule]) }}" class="fw-bold text-primary text-decoration-none">
                        {{ $fiche->matricule_vehicule }}
                      </a>
                    </td>
                    <td>{{ $poids > 0 ? number_format($poids, 0, ',', ' ') : '—' }}</td>
                    <td>
                      @if($pu !== null && (float) $pu > 0)
                        <span class="fw-semibold text-primary">{{ number_format($pu, 0, ',', ' ') }} FCFA</span>
                      @else
                        <span class="badge bg-label-warning">Non saisi</span>
                      @endif
                    </td>
                    <td class="text-end text-danger">{{ $montantGlobalFiche > 0 ? number_format($montantGlobalFiche, 0, ',', ' ') : '-' }}</td>
                    <td class="text-end text-info">{{ $avanceTableau > 0 ? number_format($avanceTableau, 0, ',', ' ') : '-' }}</td>
                    <td class="text-end text-success">{{ number_format($montantPayeFiche, 0, ',', ' ') }}</td>
                    <td class="text-end {{ $resteAPayerFiche < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ number_format($resteAPayerFiche, 0, ',', ' ') }}</td>
                    <td class="text-nowrap">
                      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalPU{{ $fiche->id }}" title="Saisir le prix unitaire">
                        <i class="bx bx-money me-1"></i>Prix unitaire
                      </button>
                      <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiementFiche{{ $fiche->id }}" title="Enregistrer un paiement">
                        <i class="bx bx-plus"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted py-4">Aucune fiche de sortie pour ce transporteur</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #d1e7dd; border-bottom: 1px solid #badbcc;">
            <h5 class="card-title mb-0" style="color: #0f5132;"><i class="bx bx-plus-circle me-2"></i>Paiements ({{ $paiementsGestion->count() }})</h5>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPaiementGestion">
              <i class="bx bx-plus"></i>
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
                @forelse($paiementsGestion as $paiement)
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
                    <td colspan="3" class="text-center text-muted">Aucun paiement enregistré</td>
                  </tr>
                @endforelse
              </tbody>
              @if($paiementsGestion->count() > 0)
                <tfoot>
                  <tr class="table-success">
                    <td colspan="2"><strong>Total paiements</strong></td>
                    <td class="text-end"><strong>{{ number_format($montantPayeGestion, 0, ',', ' ') }} FCFA</strong></td>
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

<div class="modal fade" id="modalPaiementGestion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Nouveau paiement - {{ $transporteur->nom }} {{ $transporteur->prenoms }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('gestionfinanciere.paiement_transporteur.store', $transporteur) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Reste à payer:</strong> {{ number_format($resteAPayer, 0, ',', ' ') }} FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant</label>
            <input type="text" id="montant_display" class="form-control" required placeholder="0">
            <input type="hidden" name="montant" id="montant_hidden">
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" id="mode_paiement" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>
          <div class="mb-3" id="reference_container" style="display: none;">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" placeholder="Numéro de chèque ou référence">
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

<div class="modal fade" id="modalHistorique" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Historique des paiements par fiche</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Filtrer par véhicule</label>
          <select id="filtreVehiculeHistorique" class="form-select">
            <option value="">Tous les véhicules</option>
            @foreach($vehicules as $vehicule)
              <option value="{{ $vehicule }}">{{ $vehicule }}</option>
            @endforeach
          </select>
        </div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Date</th>
                <th>Véhicule</th>
                <th>Montant</th>
                <th>Observation</th>
              </tr>
            </thead>
            <tbody id="historiqueBody">
              <tr><td colspan="4" class="text-center">Chargement...</td></tr>
            </tbody>
            <tfoot>
              <tr class="fw-bold">
                <td colspan="2">Total</td>
                <td id="totalHistorique" class="text-success">0 FCFA</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

@foreach($fichesSortie as $fiche)
  @php
    $poids = $ticketFicheService->poidsEffectif($fiche);
    $numeroTicket = $ticketFicheService->numeroTicketEffectif($fiche);
    $pu = $fiche->prix_unitaire_transport;
    $montantGlobalFiche = $pu ? ($poids * $pu) : 0;
    $depensesTableau = \App\Models\Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
        ->whereDate('date_depense', '>=', $fiche->date_chargement)
        ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
        ->sum('montant');
    $avanceTableau = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0) + $depensesTableau;
    $montantPayeFiche = $fiche->montant_paye_transporteur ?? 0;
    $resteAPayerFiche = $montantGlobalFiche - $avanceTableau - $montantPayeFiche;
  @endphp

  <div class="modal fade" id="modalPU{{ $fiche->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('gestionfinanciere.transporteur.updatePU', $fiche->id) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Prix unitaire transport</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-light border mb-3">
              <div><strong>Véhicule :</strong> {{ $fiche->matricule_vehicule }}</div>
              <div><strong>Date :</strong> {{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</div>
              <div><strong>Poids :</strong> {{ $poids ? number_format($poids, 0, ',', ' ') . ' kg' : '-' }}</div>
              <div><strong>Ticket :</strong> {{ $numeroTicket ?? '—' }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Prix unitaire (FCFA / kg) <span class="text-danger">*</span></label>
              <input
                type="number"
                name="prix_unitaire"
                class="form-control form-control-lg"
                value="{{ $pu ?? '' }}"
                min="0"
                step="1"
                required
                placeholder="Ex: 150"
              />
              <small class="text-muted">Saisie manuelle — non repris depuis le ticket.</small>
            </div>
            @if($poids > 0)
              <div class="text-muted small">
                Montant calculé : <strong class="text-danger">{{ $pu ? number_format($poids * $pu, 0, ',', ' ') : '—' }} FCFA</strong>
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalPaiementFiche{{ $fiche->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('gestionfinanciere.transporteur.paiement', $fiche->id) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Paiement fiche - {{ $fiche->matricule_vehicule }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="mb-2"><strong>Reste à payer:</strong> <span class="{{ $resteAPayerFiche < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($resteAPayerFiche, 0, ',', ' ') }} FCFA</span></p>
            <div class="mb-3">
              <label class="form-label">Montant (FCFA)</label>
              <input type="text" name="montant" class="form-control montant-input-fiche" required placeholder="0">
            </div>
            <div class="mb-3">
              <label class="form-label">Date du paiement</label>
              <input type="date" name="date_paiement" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Observation</label>
              <textarea name="observation" class="form-control" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach
@endsection

@section('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  var montantDisplay = document.getElementById('montant_display');
  var montantHidden = document.getElementById('montant_hidden');

  if (montantDisplay) {
    montantDisplay.addEventListener('input', function(e) {
      var value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
      if (value) {
        montantHidden.value = value;
        e.target.value = parseInt(value, 10).toLocaleString('fr-FR').replace(/,/g, ' ');
      } else {
        montantHidden.value = '';
        e.target.value = '';
      }
    });
  }

  var modePaiement = document.getElementById('mode_paiement');
  var refContainer = document.getElementById('reference_container');
  if (modePaiement && refContainer) {
    modePaiement.addEventListener('change', function() {
      refContainer.style.display = (this.value === 'Chèque' || this.value === 'Virement') ? 'block' : 'none';
    });
  }

  function formatNumber(value) {
    value = value.replace(/\D/g, '');
    return value ? parseInt(value, 10).toLocaleString('fr-FR').replace(/\u202F/g, ' ').replace(/,/g, ' ') : '';
  }

  document.querySelectorAll('.montant-input-fiche').forEach(function(input) {
    input.addEventListener('input', function() {
      this.value = formatNumber(this.value);
    });
  });

  document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function() {
      form.querySelectorAll('.montant-input-fiche').forEach(function(input) {
        input.value = input.value.replace(/\s/g, '');
      });
    });
  });

  function chargerHistorique(vehicule) {
    var url = '{{ route("gestionfinanciere.transporteur.historique", $transporteur) }}';
    if (vehicule) {
      url += '?vehicule=' + encodeURIComponent(vehicule);
    }

    fetch(url)
      .then(function(response) { return response.json(); })
      .then(function(data) {
        var tbody = document.getElementById('historiqueBody');
        var total = 0;

        if (!data.paiements.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucun paiement trouvé</td></tr>';
        } else {
          tbody.innerHTML = '';
          data.paiements.forEach(function(paiement) {
            var date = new Date(paiement.date_paiement);
            var montant = parseFloat(paiement.montant);
            total += montant;
            tbody.innerHTML += '<tr><td>' + date.toLocaleDateString('fr-FR') + '</td><td class="fw-bold">' + paiement.matricule_vehicule + '</td><td class="text-success">' + montant.toLocaleString('fr-FR') + ' FCFA</td><td>' + (paiement.observation || '-') + '</td></tr>';
          });
        }

        document.getElementById('totalHistorique').textContent = total.toLocaleString('fr-FR') + ' FCFA';
      });
  }

  var modalHistorique = document.getElementById('modalHistorique');
  if (modalHistorique) {
    modalHistorique.addEventListener('shown.bs.modal', function() {
      chargerHistorique();
    });
  }

  var filtreHistorique = document.getElementById('filtreVehiculeHistorique');
  if (filtreHistorique) {
    filtreHistorique.addEventListener('change', function() {
      chargerHistorique(this.value);
    });
  }
});
</script>
@endsection
