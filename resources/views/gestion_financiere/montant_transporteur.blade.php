@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="mb-4">Montant Autres Transporteurs</h4>

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
        <div class="card bg-danger text-white">
          <div class="card-body">
            <h6 class="card-title text-white">Montant Global (Poids × PU)</h6>
            <h3 class="mb-0">{{ number_format($montantDu ?? 0, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <h6 class="card-title text-white">Montant Payé</h6>
            <h3 class="mb-0">{{ number_format($montantPaye ?? 0, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <h6 class="card-title text-white">Reste à Payer</h6>
            <h3 class="mb-0">{{ number_format($resteAPayer ?? 0, 0, ',', ' ') }} FCFA</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Filtres de recherche</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('gestionfinanciere.montant_transporteur') }}">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Véhicule</label>
              <select name="vehicule" class="form-select">
                <option value="">Tous les véhicules</option>
                @foreach($vehicules ?? [] as $vehicule)
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
              <button type="submit" class="btn btn-primary me-2"><i class="bx bx-search"></i> Filtrer</button>
              <a href="{{ route('gestionfinanciere.montant_transporteur') }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des fiches de sortie - Transporteur "Autre" ({{ isset($fichesSortie) ? $fichesSortie->count() : 0 }})</h5>
        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalHistorique">
          <i class="bx bx-history"></i> Historique des paiements
        </button>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Date Chargement</th>
              <th>Véhicule</th>
              <th>Pont</th>
              <th>Usine</th>
              <th>Poids (kg)</th>
              <th>PU</th>
              <th>Montant Global</th>
              <th>Avance</th>
              <th>Montant Payé</th>
              <th>Reste à Payer</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fichesSortie ?? [] as $fiche)
              @php
                $poids = $fiche->poids_pont ?? 0;
                $pu = $fiche->prix_unitaire_transport;
                $montantGlobalFiche = $pu ? ($poids * $pu) : 0;
                
                // Calculer l'avance (Carburant + Frais Route + Dépenses)
                $depensesTableau = \App\Models\Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
                    ->whereDate('date_depense', '>=', $fiche->date_chargement)
                    ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
                    ->sum('montant');
                $avanceTableau = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0) + $depensesTableau;
                
                $montantPayeFiche = $fiche->montant_paye_transporteur ?? 0;
                // Reste à Payer = Montant Global - Avance - Montant Payé
                $resteAPayerFiche = $montantGlobalFiche - $avanceTableau - $montantPayeFiche;
              @endphp
              <tr>
                <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d-m-Y') : '-' }}</td>
                <td class="fw-bold">{{ $fiche->matricule_vehicule ?? '-' }}</td>
                <td>{{ $fiche->nom_pont ?? '-' }}</td>
                <td>{{ $fiche->usine ?? '-' }}</td>
                <td>{{ $poids ? number_format($poids, 0, ',', ' ') : '-' }}</td>
                <td>{{ $pu !== null ? number_format($pu, 0, ',', ' ') : '-' }}</td>
                <td class="text-danger fw-bold">{{ $montantGlobalFiche > 0 ? number_format($montantGlobalFiche, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                <td class="text-info fw-bold">{{ $avanceTableau > 0 ? number_format($avanceTableau, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                <td class="text-success">{{ number_format($montantPayeFiche, 0, ',', ' ') }} FCFA</td>
                <td class="{{ $resteAPayerFiche < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ number_format($resteAPayerFiche, 0, ',', ' ') }} FCFA</td>
                <td>
                  <button type="button" class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalPaiement{{ $fiche->id }}">
                    <i class="bx bx-plus"></i> Paiement
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-info me-1" data-bs-toggle="modal" data-bs-target="#modalDetails{{ $fiche->id }}">
                    <i class="bx bx-show"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPU{{ $fiche->id }}">
                    <i class="bx bx-edit"></i> PU
                  </button>
                </td>
              </tr>

              <!-- Modal Détails -->
              <div class="modal fade" id="modalDetails{{ $fiche->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Détails de la fiche de sortie</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Véhicule:</div>
                        <div class="col-7">{{ $fiche->matricule_vehicule ?? '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Date Chargement:</div>
                        <div class="col-7">{{ $fiche->date_chargement ? $fiche->date_chargement->format('d-m-Y') : '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Date Déchargement:</div>
                        <div class="col-7">{{ $fiche->date_dechargement ? $fiche->date_dechargement->format('d-m-Y') : '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Pont:</div>
                        <div class="col-7">{{ $fiche->nom_pont ?? '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Agent:</div>
                        <div class="col-7">{{ $fiche->nom_agent ?? '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Usine:</div>
                        <div class="col-7">{{ $fiche->usine ?? '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Poids (kg):</div>
                        <div class="col-7">{{ $poids ? number_format($poids, 0, ',', ' ') : '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Carburant:</div>
                        <div class="col-7">{{ $fiche->carburant ? number_format($fiche->carburant, 0, ',', ' ') . ' FCFA' : '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Frais de Route:</div>
                        <div class="col-7">{{ $fiche->frais_route ? number_format($fiche->frais_route, 0, ',', ' ') . ' FCFA' : '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Prix Unitaire:</div>
                        <div class="col-7">{{ $pu !== null ? number_format($pu, 0, ',', ' ') . ' FCFA' : '-' }}</div>
                      </div>
                      @php
                        $depenses = \App\Models\Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
                            ->whereDate('date_depense', '>=', $fiche->date_chargement)
                            ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
                            ->get();
                        $totalDepenses = $depenses->sum('montant');
                        $avance = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0) + $totalDepenses;
                      @endphp

                      @if($depenses->count() > 0)
                        <hr>
                        <h6 class="fw-bold mb-3">Dépenses relatives à cette sortie</h6>
                        @foreach($depenses as $depense)
                          <div class="row mb-2">
                            <div class="col-5">{{ $depense->type_depense ?? $depense->description }}</div>
                            <div class="col-7 text-danger">{{ number_format($depense->montant, 0, ',', ' ') }} FCFA</div>
                          </div>
                        @endforeach
                        <div class="row mb-2 mt-2">
                          <div class="col-5 fw-bold">Total Dépenses:</div>
                          <div class="col-7 text-danger fw-bold">{{ number_format($totalDepenses, 0, ',', ' ') }} FCFA</div>
                        </div>
                      @endif

                      <hr>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Avance:</div>
                        <div class="col-7 text-info fw-bold">{{ number_format($avance, 0, ',', ' ') }} FCFA</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Montant Global:</div>
                        <div class="col-7 text-danger fw-bold">{{ $montantGlobalFiche > 0 ? number_format($montantGlobalFiche, 0, ',', ' ') . ' FCFA' : '-' }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Montant Payé:</div>
                        <div class="col-7 text-success">{{ number_format($montantPayeFiche, 0, ',', ' ') }} FCFA</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-5 fw-bold">Reste à Payer:</div>
                        <div class="col-7 {{ $resteAPayerFiche < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ number_format($resteAPayerFiche, 0, ',', ' ') }} FCFA</div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal pour modifier le PU -->
              <div class="modal fade" id="modalPU{{ $fiche->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                  <div class="modal-content">
                    <form action="{{ route('gestionfinanciere.transporteur.updatePU', $fiche->id) }}" method="POST">
                      @csrf
                      @method('PUT')
                      <div class="modal-header">
                        <h5 class="modal-title">Modifier PU</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p class="mb-2"><strong>Véhicule:</strong> {{ $fiche->matricule_vehicule }}</p>
                        <p class="mb-3"><strong>Poids:</strong> {{ number_format($poids, 0, ',', ' ') }} kg</p>
                        <div class="mb-3">
                          <label class="form-label">Prix Unitaire (FCFA)</label>
                          <input type="number" name="prix_unitaire" class="form-control" value="{{ $pu ?? '' }}" min="0" step="1" required>
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

              <!-- Modal Paiement -->
              <div class="modal fade" id="modalPaiement{{ $fiche->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form action="{{ route('gestionfinanciere.transporteur.paiement', $fiche->id) }}" method="POST">
                      @csrf
                      <div class="modal-header">
                        <h5 class="modal-title">Effectuer un paiement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p class="mb-2"><strong>Véhicule:</strong> {{ $fiche->matricule_vehicule }}</p>
                        <p class="mb-2"><strong>Montant Global:</strong> <span class="text-danger">{{ number_format($montantGlobalFiche, 0, ',', ' ') }} FCFA</span></p>
                        <p class="mb-2"><strong>Avance:</strong> <span class="text-info">{{ number_format($avanceTableau, 0, ',', ' ') }} FCFA</span></p>
                        <p class="mb-2"><strong>Déjà Payé:</strong> <span class="text-success">{{ number_format($montantPayeFiche, 0, ',', ' ') }} FCFA</span></p>
                        <p class="mb-3"><strong>Reste à Payer:</strong> <span class="{{ $resteAPayerFiche < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($resteAPayerFiche, 0, ',', ' ') }} FCFA</span></p>
                        <div class="mb-3">
                          <label class="form-label">Montant du paiement (FCFA)</label>
                          <input type="text" name="montant" class="form-control montant-input" data-max="{{ $resteAPayerFiche }}" required placeholder="Ex: 1 000 000">
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Date du paiement</label>
                          <input type="date" name="date_paiement" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Observation (optionnel)</label>
                          <textarea name="observation" class="form-control" rows="2"></textarea>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Enregistrer le paiement</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            @empty
              <tr>
                <td colspan="11" class="text-center">Aucune fiche de sortie pour le transporteur "Autre"</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Historique des paiements -->
    <div class="modal fade" id="modalHistorique" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Historique des paiements</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Filtrer par véhicule</label>
              <select id="filtreVehiculeHistorique" class="form-select">
                <option value="">Tous les véhicules</option>
                @foreach($vehicules ?? [] as $vehicule)
                  <option value="{{ $vehicule }}">{{ $vehicule }}</option>
                @endforeach
              </select>
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="tableHistorique">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Véhicule</th>
                    <th>Montant</th>
                    <th>Observation</th>
                  </tr>
                </thead>
                <tbody id="historiqueBody">
                  <tr>
                    <td colspan="4" class="text-center">Chargement...</td>
                  </tr>
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
  </div>
</div>
@endsection

@section('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour formater un nombre avec des espaces
    function formatNumber(value) {
        // Enlever tout sauf les chiffres
        value = value.replace(/\D/g, '');
        if (value) {
            return parseInt(value).toLocaleString('fr-FR').replace(/\u202F/g, ' ').replace(/,/g, ' ');
        }
        return '';
    }

    // Appliquer le formatage à tous les champs montant-input
    function initMontantInputs() {
        document.querySelectorAll('.montant-input').forEach(function(input) {
            // Supprimer les anciens listeners pour éviter les doublons
            input.removeEventListener('input', handleInput);
            input.addEventListener('input', handleInput);
        });
    }

    function handleInput(e) {
        let cursorPos = this.selectionStart;
        let oldLength = this.value.length;
        this.value = formatNumber(this.value);
        let newLength = this.value.length;
        // Ajuster la position du curseur
        cursorPos = cursorPos + (newLength - oldLength);
        this.setSelectionRange(cursorPos, cursorPos);
    }

    // Initialiser au chargement
    initMontantInputs();

    // Réinitialiser quand un modal s'ouvre
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function() {
            initMontantInputs();
        });
    });

    // Avant soumission, convertir en nombre
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            form.querySelectorAll('.montant-input').forEach(function(input) {
                input.value = input.value.replace(/\s/g, '');
            });
        });
    });

    // Historique des paiements
    function chargerHistorique(vehicule = '') {
        let url = '{{ route("gestionfinanciere.transporteur.historique") }}';
        if (vehicule) {
            url += '?vehicule=' + encodeURIComponent(vehicule);
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                let tbody = document.getElementById('historiqueBody');
                let total = 0;
                
                if (data.paiements.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucun paiement trouvé</td></tr>';
                } else {
                    tbody.innerHTML = '';
                    data.paiements.forEach(function(paiement) {
                        let date = new Date(paiement.date_paiement);
                        let dateStr = date.toLocaleDateString('fr-FR');
                        let montant = parseFloat(paiement.montant);
                        total += montant;
                        
                        tbody.innerHTML += `
                            <tr>
                                <td>${dateStr}</td>
                                <td class="fw-bold">${paiement.matricule_vehicule}</td>
                                <td class="text-success">${montant.toLocaleString('fr-FR')} FCFA</td>
                                <td>${paiement.observation || '-'}</td>
                            </tr>
                        `;
                    });
                }
                
                document.getElementById('totalHistorique').textContent = total.toLocaleString('fr-FR') + ' FCFA';
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('historiqueBody').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Erreur de chargement</td></tr>';
            });
    }

    // Charger l'historique quand le modal s'ouvre
    document.getElementById('modalHistorique').addEventListener('shown.bs.modal', function() {
        chargerHistorique();
    });

    // Filtrer par véhicule
    document.getElementById('filtreVehiculeHistorique').addEventListener('change', function() {
        chargerHistorique(this.value);
    });
});
</script>
@endsection
