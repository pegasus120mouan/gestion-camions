@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header avec infos du pont -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('ponts.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux ponts
        </a>
        <h4 class="mb-0">
          <i class="bx bx-package text-primary me-2"></i>
          Gestion du Stock - {{ $pont['nom_pont'] ?? 'Pont' }}
        </h4>
        <small class="text-muted">Code: {{ $pont['code_pont'] ?? '-' }} | Gérant: {{ $pont['gerant'] ?? '-' }}</small>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
        <i class="bx bx-plus me-1"></i> Ajouter un stock
      </button>
    </div>

    @if(!empty($external_error))
      <div class="alert alert-danger">{{ $external_error }}</div>
    @endif

    @php
      // Calculer les statistiques pour le stock ouvert uniquement
      $stockOuvert = $stocks->where('type', 'entree')->where('statut', 'ouvert')->first();
      $stockOuvertEntrees = $stockOuvert ? (float)$stockOuvert->quantite : 0;
      
      // Sorties liées à ce stock spécifique (via stock_id)
      $sortiesFichesOuvert = 0;
      if ($stockOuvert) {
          $sortiesFichesOuvert = \App\Models\FicheSortie::where('stock_id', $stockOuvert->id)
              ->whereNotNull('date_dechargement')
              ->whereNotNull('poids_pont')
              ->sum('poids_pont');
      }
      $ecartOuvert = $sortiesFichesOuvert - $stockOuvertEntrees;
      $stockDisponibleOuvert = $stockOuvertEntrees - $sortiesFichesOuvert;
    @endphp

    <!-- Résumé du stock OUVERT -->
    @if($stockOuvert)
    <div class="card mb-4 border-primary">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0 text-white">
            <i class="bx bx-package me-2"></i>Stock Actif: {{ $stockOuvert->code_stock ?? 'N/A' }}
          </h5>
          <small>Ouvert le {{ $stockOuvert->date_mouvement ? $stockOuvert->date_mouvement->format('d/m/Y') : '-' }}</small>
        </div>
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#fermerStockModal-{{ $stockOuvert->id }}">
          <i class="bx bx-lock me-1"></i> Fermer ce stock
        </button>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, #696cff22 0%, #696cff11 100%);">
              <h6 class="text-muted mb-1">Entrée</h6>
              <h3 class="mb-0 text-primary">{{ number_format($stockOuvertEntrees, 0, ',', ' ') }} kg</h3>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, #28c76f22 0%, #28c76f11 100%);">
              <h6 class="text-muted mb-1">Sorties</h6>
              <h3 class="mb-0 text-success">{{ number_format($sortiesFichesOuvert, 0, ',', ' ') }} kg</h3>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, {{ $ecartOuvert > 0 ? '#28c76f22' : '#ea545522' }} 0%, {{ $ecartOuvert > 0 ? '#28c76f11' : '#ea545511' }} 100%);">
              <h6 class="text-muted mb-1">Écart</h6>
              <h3 class="mb-0 {{ $ecartOuvert > 0 ? 'text-success' : 'text-danger' }}">
                @if($ecartOuvert > 0)
                  <i class="bx bx-trending-up"></i>
                @elseif($ecartOuvert < 0)
                  <i class="bx bx-trending-down"></i>
                @endif
                {{ number_format($ecartOuvert, 0, ',', ' ') }} kg
              </h3>
              <small class="{{ $ecartOuvert > 0 ? 'text-success' : 'text-danger' }}">{{ $ecartOuvert > 0 ? 'Bénéfice' : ($ecartOuvert < 0 ? 'Perte' : 'Équilibré') }}</small>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, #00cfe822 0%, #00cfe811 100%);">
              <h6 class="text-muted mb-1">Stock disponible</h6>
              <h3 class="mb-0 text-info">{{ number_format(max(0, $stockDisponibleOuvert), 0, ',', ' ') }} kg</h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Fermer Stock -->
    <div class="modal fade" id="fermerStockModal-{{ $stockOuvert->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title">
              <i class="bx bx-lock me-2"></i>Fermer le stock {{ $stockOuvert->code_stock }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ route('ponts.stock.fermer', ['id_pont' => $pont['id_pont'], 'stock_id' => $stockOuvert->id]) }}">
            @csrf
            <div class="modal-body">
              <div class="alert alert-warning">
                <i class="bx bx-info-circle me-1"></i>
                <strong>Attention !</strong> Une fois fermé, aucune sortie ne pourra être effectuée sur ce stock.
              </div>
              <div class="mb-3">
                <p><strong>Résumé du stock:</strong></p>
                <ul class="list-unstyled">
                  <li><i class="bx bx-right-arrow-alt text-primary"></i> Entrée: <strong>{{ number_format($stockOuvertEntrees, 0, ',', ' ') }} kg</strong></li>
                  <li><i class="bx bx-right-arrow-alt text-success"></i> Sorties: <strong>{{ number_format($sortiesFichesOuvert, 0, ',', ' ') }} kg</strong></li>
                  <li><i class="bx bx-right-arrow-alt text-warning"></i> Écart: <strong>{{ number_format($ecartOuvert, 0, ',', ' ') }} kg</strong></li>
                  <li><i class="bx bx-right-arrow-alt text-info"></i> Stock restant: <strong>{{ number_format(max(0, $stockDisponibleOuvert), 0, ',', ' ') }} kg</strong></li>
                </ul>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-warning">
                <i class="bx bx-lock me-1"></i> Confirmer la fermeture
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    @else
    <div class="alert alert-info mb-4">
      <i class="bx bx-info-circle me-1"></i>
      Aucun stock ouvert. Cliquez sur "Ajouter un stock" pour créer une nouvelle entrée.
    </div>
    @endif

    <!-- Historique des stocks (fermés) -->
    @php
      $stocksFermes = $stocks->where('type', 'entree')->where('statut', 'ferme');
    @endphp
    @if($stocksFermes->count() > 0)
    <div class="card mb-4">
      <div class="card-header bg-secondary text-white">
        <h5 class="mb-0 text-white">
          <i class="bx bx-history me-2"></i>Historique des stocks fermés
        </h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Code Stock</th>
              <th>Date Entrée</th>
              <th>Date Fermeture</th>
              <th class="text-end">Entrée (kg)</th>
              <th class="text-end">Sorties (kg)</th>
              <th class="text-end">Écart (kg)</th>
            </tr>
          </thead>
          <tbody>
            @foreach($stocksFermes as $sf)
              @php
                $entree = (float)$sf->quantite;
                // Sorties liées à ce stock spécifique (via stock_id)
                $sortiesStock = \App\Models\FicheSortie::where('stock_id', $sf->id)
                    ->whereNotNull('date_dechargement')
                    ->whereNotNull('poids_pont')
                    ->sum('poids_pont');
                $ecart = $sortiesStock - $entree;
              @endphp
              <tr>
                <td><span class="badge bg-secondary">{{ $sf->code_stock ?? 'N/A' }}</span></td>
                <td>{{ $sf->date_mouvement ? $sf->date_mouvement->format('d/m/Y') : '-' }}</td>
                <td>{{ $sf->date_fermeture ? $sf->date_fermeture->format('d/m/Y') : '-' }}</td>
                <td class="text-end text-primary fw-bold">{{ number_format($entree, 0, ',', ' ') }}</td>
                <td class="text-end text-success">{{ number_format($sortiesStock, 0, ',', ' ') }}</td>
                <td class="text-end {{ $ecart > 0 ? 'text-success' : 'text-danger' }} fw-bold">
                  @if($ecart > 0)
                    <i class="bx bx-trending-up"></i>
                  @elseif($ecart < 0)
                    <i class="bx bx-trending-down"></i>
                  @endif
                  {{ number_format($ecart, 0, ',', ' ') }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    <!-- Tableau des mouvements de stock (entrées) -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Mouvements de stock (Entrées)</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Code</th>
              <th>Date</th>
              <th>Statut</th>
              <th>Quantité (kg)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stocks->where('type', 'entree') as $s)
              <tr>
                <td><span class="badge {{ $s->isOuvert() ? 'bg-primary' : 'bg-secondary' }}">{{ $s->code_stock ?? 'N/A' }}</span></td>
                <td>{{ $s->date_mouvement ? $s->date_mouvement->format('d-m-Y') : '-' }}</td>
                <td>
                  @if($s->isOuvert())
                    <span class="badge bg-success">Ouvert</span>
                  @else
                    <span class="badge bg-danger">Fermé</span>
                    <small class="text-muted d-block">{{ $s->date_fermeture ? $s->date_fermeture->format('d/m/Y') : '' }}</small>
                  @endif
                </td>
                <td>{{ number_format((float)$s->quantite, 0, ',', ' ') }}</td>
                <td>
                  @if($s->isOuvert())
                    <button class="btn btn-sm btn-outline-danger" onclick="if(confirm('Supprimer ce mouvement?')) document.getElementById('delete-stock-{{ $s->id }}').submit();">
                      <i class="bx bx-trash"></i>
                    </button>
                    <form id="delete-stock-{{ $s->id }}" action="{{ route('ponts.stock.delete', ['id_pont' => $pont['id_pont'], 'stock_id' => $s->id]) }}" method="POST" style="display:none;">
                      @csrf
                      @method('DELETE')
                    </form>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4">
                  <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                  <p class="text-muted mt-2 mb-0">Aucun mouvement de stock enregistré</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tableau des sorties (fiches déchargées) -->
    @if(isset($fichesDechargees) && $fichesDechargees->count() > 0)
    <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white">
        <h5 class="mb-0 text-white"><i class="bx bx-up-arrow-circle me-2"></i>Sorties (Fiches déchargées)</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Date déchargement</th>
              <th>Véhicule</th>
              <th>Agent</th>
              <th>Usine</th>
              <th class="text-end">Poids (kg)</th>
            </tr>
          </thead>
          <tbody>
            @foreach($fichesDechargees as $fiche)
              <tr>
                <td>{{ $fiche->date_dechargement ? $fiche->date_dechargement->format('d-m-Y') : '-' }}</td>
                <td><strong>{{ $fiche->matricule_vehicule }}</strong></td>
                <td>{{ $fiche->nom_agent ?? '-' }}</td>
                <td>{{ $fiche->usine ?? '-' }}</td>
                <td class="text-end fw-bold text-danger">{{ number_format((float)$fiche->poids_pont, 0, ',', ' ') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

  </div>
</div>

<!-- Modal Ajouter Stock -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-plus-circle me-2"></i>Ajouter un mouvement de stock
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('ponts.stock.store', ['id_pont' => $pont['id_pont']]) }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Type de mouvement <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="Entrée" readonly />
            <input type="hidden" name="type" value="entree" />
          </div>
          <div class="mb-3">
            <label class="form-label">Quantité (kg) <span class="text-danger">*</span></label>
            <input type="number" name="quantite" class="form-control" placeholder="Ex: 5000" min="0" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
