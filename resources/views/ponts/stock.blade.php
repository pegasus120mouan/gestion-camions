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
        <small class="text-muted d-block">Code: {{ $pont['code_pont'] ?? '-' }} | Gérant: {{ $pont['gerant'] ?? '-' }}</small>
      </div>
      @php
        $peutEntrerStock = $peut_entrer_stock ?? true;
        $stocksOuverts = $stocks->where('type', 'entree')->where('statut', 'ouvert');
        $parcsAvecStockOuvert = $stocksOuverts->pluck('parc_id')->toArray();
        $parcsDisponibles = ($parcs ?? collect())->filter(function($p) use ($parcsAvecStockOuvert) {
            return !in_array($p->id, $parcsAvecStockOuvert);
        });
      @endphp
      @if(!$peutEntrerStock)
        <button type="button" class="btn btn-secondary" disabled title="Pont fermé — entrées de stock interdites">
          <i class="bx bx-lock me-1"></i> Pont fermé
        </button>
      @elseif($parcsDisponibles->isEmpty())
        <button type="button" class="btn btn-secondary" disabled title="Tous les parcs ont un stock actif">
          <i class="bx bx-lock me-1"></i> Tous les parcs occupés
        </button>
      @else
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
          <i class="bx bx-plus me-1"></i> Ajouter un stock
        </button>
      @endif
      <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#addDepenseModal">
        <i class="bx bx-plus me-1"></i> Ajouter une dépense
      </button>
    </div>

    @if(!empty($external_error))
      <div class="alert alert-danger">{{ $external_error }}</div>
    @endif

    @if(!($peut_entrer_stock ?? true))
      <div class="alert alert-warning">
        <i class="bx bx-info-circle me-1"></i>
        Ce pont est <strong>fermé</strong>. Les entrées de stock (nouveau stock ou entrées supplémentaires) ne sont pas autorisées.
      </div>
    @endif

    <!-- Solde du pont -->
    <div class="card mb-4 border-warning">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg me-3" style="background: linear-gradient(135deg, #ff9f43 0%, #ffb976 100%); border-radius: 10px;">
              <i class="bx bx-wallet fs-3 text-white"></i>
            </div>
            <div>
              <h6 class="text-muted mb-0">Solde</h6>
              <h3 class="mb-0 fw-bold {{ ($solde ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($solde ?? 0, 0, ',', ' ') }} FCFA
              </h3>
            </div>
          </div>
          <a href="{{ route('approvisionnements.index', ['pont' => $pont['nom_pont'] ?? '']) }}" class="btn btn-outline-warning">
            <i class="bx bx-history me-1"></i> Voir les approvisionnements
          </a>
        </div>
      </div>
    </div>

    <!-- Résumé des stocks OUVERTS -->
    @foreach($stocksOuverts as $stockOuvert)
    @php
      $stockOuvertEntrees = $stockOuvert->total_entrees;
      $sortiesFichesOuvert = \App\Models\FicheSortie::where('stock_id', $stockOuvert->id)
          ->whereNotNull('date_dechargement')
          ->whereNotNull('poids_pont')
          ->sum('poids_pont');
      $ecartOuvert = $sortiesFichesOuvert - $stockOuvertEntrees;
      $stockDisponibleOuvert = $stockOuvertEntrees - $sortiesFichesOuvert;
    @endphp
    <div class="card mb-4 {{ $stockOuvert->isActif() ? 'border-primary' : 'border-secondary' }}">
      <div class="card-header {{ $stockOuvert->isActif() ? 'bg-primary' : 'bg-secondary' }} text-white d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0 text-white">
            <i class="bx bx-package me-2"></i>{{ $stockOuvert->isActif() ? 'Stock Actif' : 'Stock Inactif' }}: 
            <a href="#" data-bs-toggle="modal" data-bs-target="#modalStockDetail-{{ $stockOuvert->id }}" class="text-white text-decoration-underline" style="cursor: pointer;">
              {{ $stockOuvert->code_stock ?? 'N/A' }}
            </a>
            @if($stockOuvert->isInactif())
              <span class="badge bg-warning text-dark ms-2">Désactivé</span>
            @endif
          </h5>
          <small>Ouvert le {{ $stockOuvert->date_mouvement ? $stockOuvert->date_mouvement->format('d/m/Y') : '-' }} | Parc: <strong>{{ $stockOuvert->nom_parc ?? '-' }}</strong> | Produit: <strong>{{ $stockOuvert->nom_produit ?? '-' }}</strong></small>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
          <button type="button" class="btn btn-outline-light border-white text-white" data-bs-toggle="modal" data-bs-target="#supprimerStockOuvertModal-{{ $stockOuvert->id }}" title="Supprimer ce stock">
            <i class="bx bx-trash me-1"></i> Supprimer
          </button>
          <form method="POST" action="{{ route('ponts.stock.etat', ['id_pont' => $pont['id_pont'], 'stock_id' => $stockOuvert->id]) }}" class="d-inline">
            @csrf
            @if($stockOuvert->isActif())
              <button type="submit" class="btn btn-light text-dark" title="Désactiver ce stock">
                <i class="bx bx-pause me-1"></i> Désactiver
              </button>
            @else
              <button type="submit" class="btn btn-success" title="Activer ce stock">
                <i class="bx bx-play me-1"></i> Actif
              </button>
            @endif
          </form>
          <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#fermerStockModal-{{ $stockOuvert->id }}">
            <i class="bx bx-lock me-1"></i> Fermer
          </button>
        </div>
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
                  <li><i class="bx bx-right-arrow-alt text-secondary"></i> Parc: <strong>{{ $stockOuvert->nom_parc ?? '-' }}</strong> | Produit: <strong>{{ $stockOuvert->nom_produit ?? '-' }}</strong></li>
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

    <div class="modal fade" id="supprimerStockOuvertModal-{{ $stockOuvert->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title text-white">
              <i class="bx bx-trash me-2"></i>Supprimer le stock {{ $stockOuvert->code_stock }}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ route('ponts.stock.delete', ['id_pont' => $pont['id_pont'], 'stock_id' => $stockOuvert->id]) }}">
            @csrf
            @method('DELETE')
            <div class="modal-body">
              <div class="alert alert-danger">
                <i class="bx bx-error-circle me-2"></i>
                <strong>Attention !</strong> Cette action est irréversible. Les entrées de ce stock seront supprimées.
              </div>
              @if($sortiesFichesOuvert > 0)
              <div class="alert alert-warning mb-3">
                <i class="bx bx-info-circle me-2"></i>
                Ce stock a <strong>{{ number_format($sortiesFichesOuvert, 0, ',', ' ') }} kg</strong> de sorties déchargées enregistrées. Les fiches de sortie resteront dans l’historique mais ne seront plus liées à ce stock.
              </div>
              @endif
              <p><strong>Code :</strong> {{ $stockOuvert->code_stock ?? 'N/A' }}</p>
              <p><strong>Parc :</strong> {{ $stockOuvert->nom_parc ?? '-' }}</p>
              <p><strong>Produit :</strong> {{ $stockOuvert->nom_produit ?? '-' }}</p>
              <p><strong>Entrée totale :</strong> {{ number_format($stockOuvertEntrees, 0, ',', ' ') }} kg</p>
              @if($sortiesFichesOuvert > 0)
              <p><strong>Sorties (déchargées) :</strong> {{ number_format($sortiesFichesOuvert, 0, ',', ' ') }} kg</p>
              @endif
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-danger">
                <i class="bx bx-trash me-1"></i> Supprimer définitivement
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    @endforeach

    @if($stocksOuverts->isEmpty())
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
              <th>Parc</th>
              <th>Date Entrée</th>
              <th>Date Fermeture</th>
              <th class="text-end">Entrée (kg)</th>
              <th class="text-end">Sorties (kg)</th>
              <th class="text-end">Écart (kg)</th>
              <th class="text-center" style="width: 80px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($stocksFermes as $sf)
              @php
                $entree = $sf->total_entrees;
                // Sorties liées à ce stock spécifique (via stock_id)
                $sortiesStock = \App\Models\FicheSortie::where('stock_id', $sf->id)
                    ->whereNotNull('date_dechargement')
                    ->whereNotNull('poids_pont')
                    ->sum('poids_pont');
                $ecart = $sortiesStock - $entree;
              @endphp
              <tr>
                <td>
                  <a href="#" data-bs-toggle="modal" data-bs-target="#modalStockDetail-{{ $sf->id }}" class="text-decoration-none">
                    <span class="badge bg-secondary" style="cursor: pointer;">{{ $sf->code_stock ?? 'N/A' }}</span>
                  </a>
                </td>
                <td><span class="badge bg-warning">{{ $sf->nom_parc ?? '-' }}</span></td>
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
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteStock-{{ $sf->id }}" title="Supprimer ce stock">
                    <i class="bx bx-trash"></i>
                  </button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    <!-- Section Dépenses du Pont -->
    <div class="card mb-4">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0 text-white"><i class="bx bx-money-withdraw me-2"></i>Dépenses du Pont</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Libellé</th>
              <th class="text-end">Montant</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($depensesPont ?? [] as $dep)
              <tr>
                <td>{{ $dep->date_depense ? $dep->date_depense->format('d/m/Y') : '-' }}</td>
                <td><strong>{{ $dep->libelle }}</strong></td>
                <td class="text-end"><strong class="text-danger">{{ number_format((float)$dep->montant, 0, ',', ' ') }} FCFA</strong></td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteDepenseModal{{ $dep->id }}">
                    <i class="bx bx-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-4">
                  <i class="bx bx-receipt text-muted" style="font-size: 2rem;"></i>
                  <p class="text-muted mt-2 mb-0">Aucune dépense enregistrée</p>
                </td>
              </tr>
            @endforelse
          </tbody>
          @if(($depensesPont ?? collect())->count() > 0)
          <tfoot class="table-danger">
            <tr>
              <td colspan="2" class="text-end fw-bold">Total Dépenses:</td>
              <td class="text-end fw-bold text-danger">{{ number_format($totalDepensesPont ?? 0, 0, ',', ' ') }} FCFA</td>
              <td></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>

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
              <th>Produit</th>
              <th>Statut</th>
              <th>Quantité (kg)</th>
              <th class="text-end">Montant</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stocks->where('type', 'entree') as $s)
              <tr>
                <td><span class="badge {{ $s->isOuvert() ? 'bg-primary' : 'bg-secondary' }}">{{ $s->code_stock ?? 'N/A' }}</span></td>
                <td>{{ $s->date_mouvement ? $s->date_mouvement->format('d-m-Y') : '-' }}</td>
                <td><span class="badge bg-info">{{ $s->nom_produit ?? '-' }}</span></td>
                <td>
                  @if($s->isOuvert())
                    <span class="badge bg-success">Ouvert</span>
                  @else
                    <span class="badge bg-danger">Fermé</span>
                    <small class="text-muted d-block">{{ $s->date_fermeture ? $s->date_fermeture->format('d/m/Y') : '' }}</small>
                  @endif
                </td>
                <td>{{ number_format((float)$s->quantite, 0, ',', ' ') }}</td>
                <td class="text-end">
                  @if($s->montant_total > 0)
                    <strong class="text-success">{{ number_format((float)$s->montant_total, 0, ',', ' ') }} FCFA</strong>
                    <small class="text-muted d-block">{{ number_format((float)$s->prix_unitaire, 0, ',', ' ') }} FCFA/kg</small>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($s->isOuvert())
                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteStockModal{{ $s->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4">
                  <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                  <p class="text-muted mt-2 mb-0">Aucun mouvement de stock enregistré</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modals de suppression des stocks -->
    @foreach($stocks->where('type', 'entree')->where('statut', 'ouvert') as $s)
    <div class="modal fade" id="deleteStockModal{{ $s->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center py-4">
            <div class="mb-3">
              <i class="bx bx-error-circle text-warning" style="font-size: 3rem;"></i>
            </div>
            <h5 class="mb-2">Supprimer ce stock ?</h5>
            <p class="text-muted mb-0 small">
              <strong>{{ $s->code_stock }}</strong><br>
              @if($s->montant_total > 0)
                Le montant de <strong class="text-success">{{ number_format((float)$s->montant_total, 0, ',', ' ') }} FCFA</strong> sera recrédité au solde.
              @endif
            </p>
          </div>
          <div class="modal-footer justify-content-center border-0 pt-0">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <form action="{{ route('ponts.stock.delete', ['id_pont' => $pont['id_pont'], 'stock_id' => $s->id]) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger">
                <i class="bx bx-trash me-1"></i>Supprimer
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    @endforeach

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
              <th>Produit</th>
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
                <td>
                  @if($fiche->nom_produit)
                    <span class="badge bg-info">{{ $fiche->nom_produit }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
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
            <label class="form-label">Parc <span class="text-danger">*</span></label>
            <select name="parc_id" class="form-select" required>
              <option value="">-- Sélectionner un parc --</option>
              @foreach($parcsDisponibles ?? [] as $parc)
                <option value="{{ $parc->id }}">{{ $parc->nom }} ({{ $parc->code }})</option>
              @endforeach
            </select>
            @if(($parcs ?? collect())->isEmpty())
              <small class="text-danger">Aucun parc disponible. <a href="{{ route('parcs.index') }}">Créer un parc</a></small>
            @elseif($parcsDisponibles->isEmpty())
              <small class="text-warning">Tous les parcs ont déjà un stock actif.</small>
            @endif
          </div>
          <div class="mb-3">
            <label class="form-label">Produit <span class="text-danger">*</span></label>
            <select name="produit_id" class="form-select" required>
              <option value="">-- Sélectionner un produit --</option>
              @foreach(\App\Models\Produit::orderBy('nom')->get() as $produit)
                <option value="{{ $produit->id }}">{{ $produit->nom }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantité (kg) <span class="text-danger">*</span></label>
            <input type="number" name="quantite" id="stock_quantite" class="form-control" placeholder="Ex: 5000" min="0" required onchange="calculerMontantStock()" oninput="calculerMontantStock()" />
          </div>
          <div class="mb-3">
            <label class="form-label">Prix unitaire (FCFA/kg)</label>
            <input type="text" id="prix_unitaire_display" class="form-control" placeholder="Ex: 150" onchange="calculerMontantStock()" oninput="formatPrixUnitaire(this); calculerMontantStock()" />
            <input type="hidden" name="prix_unitaire" id="prix_unitaire_hidden" value="0" />
          </div>
          <div class="mb-3">
            <label class="form-label">Montant total</label>
            <div class="input-group">
              <input type="text" id="montant_total_display" class="form-control fw-bold" style="background-color: #e9ecef; color: #495057;" readonly />
              <span class="input-group-text">FCFA</span>
            </div>
            <small class="text-muted">Solde: <strong class="{{ ($solde ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($solde ?? 0, 0, ',', ' ') }} FCFA</strong></small>
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

<!-- Modals pour les détails des stocks fermés -->
@foreach($stocks->where('type', 'entree') as $stockItem)
@php
  $fichesStock = \App\Models\FicheSortie::where('stock_id', $stockItem->id)
      ->whereNotNull('date_dechargement')
      ->whereNotNull('poids_pont')
      ->orderBy('date_dechargement', 'desc')
      ->get();
  $totalSortiesModal = $fichesStock->sum('poids_pont');
@endphp
<div class="modal fade" id="modalStockDetail-{{ $stockItem->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header {{ $stockItem->isOuvert() ? 'bg-primary' : 'bg-secondary' }} text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-package me-2"></i>Détails du stock: {{ $stockItem->code_stock ?? 'N/A' }}
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Résumé du stock -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="p-3 rounded bg-light">
              <p class="mb-1"><strong>Parc:</strong> {{ $stockItem->nom_parc ?? '-' }}</p>
              <p class="mb-1"><strong>Produit:</strong> <span class="badge bg-info">{{ $stockItem->nom_produit ?? '-' }}</span></p>
              <p class="mb-1"><strong>Date d'entrée:</strong> {{ $stockItem->date_mouvement ? $stockItem->date_mouvement->format('d/m/Y') : '-' }}</p>
              <p class="mb-1"><strong>Date de fermeture:</strong> {{ $stockItem->date_fermeture ? $stockItem->date_fermeture->format('d/m/Y') : 'Non fermé' }}</p>
              <p class="mb-0"><strong>Statut:</strong> 
                @if($stockItem->isOuvert())
                  <span class="badge bg-success">Ouvert</span>
                @else
                  <span class="badge bg-danger">Fermé</span>
                @endif
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded bg-light">
              <p class="mb-1"><strong>Total Entrées:</strong> <span class="text-primary fw-bold">{{ number_format($stockItem->total_entrees, 0, ',', ' ') }} kg</span></p>
              <p class="mb-1"><strong>Total Sorties:</strong> <span class="text-success fw-bold">{{ number_format($totalSortiesModal, 0, ',', ' ') }} kg</span></p>
              @php $ecartModal = $totalSortiesModal - $stockItem->total_entrees; @endphp
              <p class="mb-0"><strong>Écart:</strong> 
                <span class="{{ $ecartModal > 0 ? 'text-success' : 'text-danger' }} fw-bold">
                  @if($ecartModal > 0)<i class="bx bx-trending-up"></i>@elseif($ecartModal < 0)<i class="bx bx-trending-down"></i>@endif
                  {{ number_format($ecartModal, 0, ',', ' ') }} kg
                </span>
              </p>
            </div>
          </div>
        </div>

        <!-- Entrée du stock -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0"><i class="bx bx-down-arrow-circle me-1 text-primary"></i>Entrées de stock</h6>
          @if($stockItem->isOuvert() && ($peut_entrer_stock ?? true) && $stockItem->isActif())
          <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAddEntree-{{ $stockItem->id }}">
            <i class="bx bx-plus me-1"></i>Ajouter une entrée
          </button>
          @elseif($stockItem->isOuvert() && $stockItem->isInactif())
          <span class="badge bg-secondary">Stock désactivé</span>
          @endif
        </div>
        @php
          $entreesSupp = $stockItem->entreesStock()->orderBy('date_entree', 'desc')->get();
          $totalEntreesModal = (float)$stockItem->quantite + $entreesSupp->sum('quantite');
        @endphp
        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
          <table class="table table-sm table-hover">
            <thead class="table-light sticky-top">
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th class="text-end">Quantité (kg)</th>
                <th class="text-end">Prix unit.</th>
                <th class="text-end">Montant</th>
                @if($stockItem->isOuvert())
                <th class="text-center" style="width: 80px;">Actions</th>
                @endif
              </tr>
            </thead>
            <tbody>
              <!-- Entrée initiale -->
              <tr>
                <td>{{ $stockItem->date_mouvement ? $stockItem->date_mouvement->format('d/m/Y') : '-' }}</td>
                <td><span class="badge bg-success">Entrée initiale</span></td>
                <td class="text-end text-primary fw-bold">{{ number_format((float)$stockItem->quantite, 0, ',', ' ') }}</td>
                <td class="text-end">{{ $stockItem->prix_unitaire > 0 ? number_format((float)$stockItem->prix_unitaire, 0, ',', ' ') . ' F/kg' : '-' }}</td>
                <td class="text-end fw-bold text-danger">{{ $stockItem->montant_total > 0 ? number_format((float)$stockItem->montant_total, 0, ',', ' ') . ' F' : '-' }}</td>
                @if($stockItem->isOuvert())
                <td class="text-center text-muted">-</td>
                @endif
              </tr>
              <!-- Entrées supplémentaires -->
              @foreach($entreesSupp as $entree)
              <tr>
                <td>{{ $entree->date_entree ? $entree->date_entree->format('d/m/Y') : '-' }}</td>
                <td><span class="badge bg-info">Entrée</span></td>
                <td class="text-end text-primary fw-bold">{{ number_format((float)$entree->quantite, 0, ',', ' ') }}</td>
                <td class="text-end">{{ $entree->prix_unitaire > 0 ? number_format((float)$entree->prix_unitaire, 0, ',', ' ') . ' F/kg' : '-' }}</td>
                <td class="text-end fw-bold text-danger">{{ $entree->montant_total > 0 ? number_format((float)$entree->montant_total, 0, ',', ' ') . ' F' : '-' }}</td>
                @if($stockItem->isOuvert())
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-icon btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditEntree-{{ $entree->id }}" title="Modifier">
                    <i class="bx bx-edit-alt"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteEntree-{{ $entree->id }}" title="Supprimer">
                    <i class="bx bx-trash"></i>
                  </button>
                </td>
                @endif
              </tr>
              @endforeach
            </tbody>
            <tfoot class="table-primary">
              <tr>
                <td colspan="2"><strong>Total Entrées ({{ 1 + $entreesSupp->count() }})</strong></td>
                <td class="text-end fw-bold">{{ number_format($totalEntreesModal, 0, ',', ' ') }} kg</td>
                <td colspan="{{ $stockItem->isOuvert() ? 2 : 1 }}"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<!-- Modals Modifier/Supprimer Entrées -->
@if($stockItem->isOuvert())
@foreach($entreesSupp as $entree)
<!-- Modal Modifier Entrée -->
<div class="modal fade" id="modalEditEntree-{{ $entree->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-edit me-2"></i>Modifier l'entrée
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('ponts.stock.entree.update', ['id_pont' => $pont['id_pont'], 'stock_id' => $stockItem->id, 'entree_id' => $entree->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Quantité (kg) <span class="text-danger">*</span></label>
            <input type="number" name="quantite" class="form-control" value="{{ $entree->quantite }}" min="0" step="0.01" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Date d'entrée <span class="text-danger">*</span></label>
            <input type="date" name="date_entree" class="form-control" value="{{ $entree->date_entree ? $entree->date_entree->format('Y-m-d') : '' }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Commentaire</label>
            <textarea name="commentaire" class="form-control" rows="2">{{ $entree->commentaire }}</textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Supprimer Entrée -->
<div class="modal fade" id="modalDeleteEntree-{{ $entree->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-trash me-2"></i>Supprimer l'entrée
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('ponts.stock.entree.delete', ['id_pont' => $pont['id_pont'], 'stock_id' => $stockItem->id, 'entree_id' => $entree->id]) }}">
        @csrf
        @method('DELETE')
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="bx bx-error-circle me-2"></i>
            Êtes-vous sûr de vouloir supprimer cette entrée ?
          </div>
          <p><strong>Date:</strong> {{ $entree->date_entree ? $entree->date_entree->format('d/m/Y') : '-' }}</p>
          <p><strong>Quantité:</strong> {{ number_format((float)$entree->quantite, 0, ',', ' ') }} kg</p>
          @if($entree->commentaire)
          <p><strong>Commentaire:</strong> {{ $entree->commentaire }}</p>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger">
            <i class="bx bx-trash me-1"></i> Supprimer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach
@endif

<!-- Modal Ajouter Entrée -->
@if($stockItem->isOuvert())
<div class="modal fade" id="modalAddEntree-{{ $stockItem->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-plus-circle me-2"></i>Ajouter une entrée - {{ $stockItem->code_stock ?? 'Stock' }}
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('ponts.stock.entree', ['id_pont' => $pont['id_pont'], 'stock_id' => $stockItem->id]) }}" id="formAddEntree{{ $stockItem->id }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Quantité (kg) <span class="text-danger">*</span></label>
            <input type="number" name="quantite" class="form-control entree-quantite" data-stock="{{ $stockItem->id }}" placeholder="Ex: 5000" min="0" step="0.01" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Prix unitaire (FCFA/kg)</label>
            <input type="number" name="prix_unitaire" class="form-control entree-prix" data-stock="{{ $stockItem->id }}" placeholder="Ex: 150" min="0" step="1" />
          </div>
          <div class="mb-3">
            <label class="form-label">Montant total</label>
            <div class="input-group">
              <input type="text" class="form-control fw-bold entree-montant-display" data-stock="{{ $stockItem->id }}" style="background-color: #e9ecef;" readonly />
              <span class="input-group-text">FCFA</span>
            </div>
            <small class="text-muted">Solde: <strong class="{{ ($solde ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($solde ?? 0, 0, ',', ' ') }} FCFA</strong></small>
          </div>
          <div class="mb-3">
            <label class="form-label">Date d'entrée <span class="text-danger">*</span></label>
            <input type="date" name="date_entree" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Commentaire</label>
            <textarea name="commentaire" class="form-control" rows="2" placeholder="Optionnel..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endforeach

<!-- Modals Supprimer Stock Fermé -->
@foreach($stocksFermes as $sf)
@php
  $sortiesStockModal = \App\Models\FicheSortie::where('stock_id', $sf->id)
      ->whereNotNull('date_dechargement')
      ->whereNotNull('poids_pont')
      ->sum('poids_pont');
@endphp
<div class="modal fade" id="modalDeleteStock-{{ $sf->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-trash me-2"></i>Supprimer le stock
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('ponts.stock.delete', ['id_pont' => $pont['id_pont'], 'stock_id' => $sf->id]) }}">
        @csrf
        @method('DELETE')
        <div class="modal-body">
          <div class="alert alert-danger">
            <i class="bx bx-error-circle me-2"></i>
            <strong>Attention !</strong> Cette action est irréversible.
          </div>
          @if($sortiesStockModal > 0)
          <div class="alert alert-warning">
            <i class="bx bx-info-circle me-2"></i>
            Des sorties déchargées (<strong>{{ number_format($sortiesStockModal, 0, ',', ' ') }} kg</strong>) sont liées à ce stock. Les fiches resteront enregistrées mais ne seront plus associées à ce stock.
          </div>
          @endif
          <p><strong>Code Stock:</strong> {{ $sf->code_stock ?? 'N/A' }}</p>
          <p><strong>Date d'entrée:</strong> {{ $sf->date_mouvement ? $sf->date_mouvement->format('d/m/Y') : '-' }}</p>
          <p><strong>Date de fermeture:</strong> {{ $sf->date_fermeture ? $sf->date_fermeture->format('d/m/Y') : '-' }}</p>
          <p><strong>Entrée:</strong> {{ number_format($sf->total_entrees, 0, ',', ' ') }} kg</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger">
            <i class="bx bx-trash me-1"></i> Supprimer définitivement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<script>
function formatPrixUnitaire(input) {
  let value = input.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
  if (value) {
    input.value = parseInt(value).toLocaleString('fr-FR').replace(/,/g, ' ');
    document.getElementById('prix_unitaire_hidden').value = value;
  } else {
    document.getElementById('prix_unitaire_hidden').value = '0';
  }
}

function calculerMontantStock() {
  var quantite = parseFloat(document.getElementById('stock_quantite').value) || 0;
  var prixUnitaire = parseFloat(document.getElementById('prix_unitaire_hidden').value) || 0;
  var montantTotal = quantite * prixUnitaire;
  
  document.getElementById('montant_total_display').value = montantTotal.toLocaleString('fr-FR').replace(/,/g, ' ');
}

// Gestion des entrées supplémentaires
document.addEventListener('DOMContentLoaded', function() {
  // Calcul montant pour les entrées - quantité
  document.querySelectorAll('.entree-quantite').forEach(function(input) {
    input.addEventListener('input', function() {
      let stockId = this.dataset.stock;
      calculerMontantEntree(stockId);
    });
  });

  // Calcul montant pour les entrées - prix
  document.querySelectorAll('.entree-prix').forEach(function(input) {
    input.addEventListener('input', function() {
      let stockId = this.dataset.stock;
      calculerMontantEntree(stockId);
    });
  });
});

function calculerMontantEntree(stockId) {
  var quantite = parseFloat(document.querySelector('.entree-quantite[data-stock="' + stockId + '"]').value) || 0;
  var prixInput = document.querySelector('.entree-prix[data-stock="' + stockId + '"]');
  var prixUnitaire = parseFloat(prixInput ? prixInput.value : 0) || 0;
  var montantTotal = quantite * prixUnitaire;
  
  var montantDisplay = document.querySelector('.entree-montant-display[data-stock="' + stockId + '"]');
  if (montantDisplay) {
    montantDisplay.value = montantTotal.toLocaleString('fr-FR').replace(/,/g, ' ');
  }
}
</script>

<!-- Modal Ajouter Dépense -->
<div class="modal fade" id="addDepenseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white">
          <i class="bx bx-money-withdraw me-2"></i>Ajouter une dépense
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('ponts.depense.store', ['id_pont' => $pont['id_pont']]) }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Libellé <span class="text-danger">*</span></label>
            <input type="text" name="libelle" class="form-control" placeholder="Ex: Achat carburant" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="montant" class="form-control" placeholder="Ex: 50000" min="0" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="date_depense" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals Supprimer Dépense -->
@foreach($depensesPont ?? [] as $dep)
<div class="modal fade" id="deleteDepenseModal{{ $dep->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div class="mb-3">
          <i class="bx bx-error-circle text-warning" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2">Supprimer cette dépense ?</h5>
        <p class="text-muted mb-0 small">
          <strong>{{ $dep->libelle }}</strong><br>
          Montant: <strong class="text-danger">{{ number_format((float)$dep->montant, 0, ',', ' ') }} FCFA</strong>
        </p>
      </div>
      <div class="modal-footer justify-content-center border-0 pt-0">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('ponts.depense.delete', ['id_pont' => $pont['id_pont'], 'depense_id' => $dep->id]) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-sm btn-danger">
            <i class="bx bx-trash me-1"></i>Supprimer
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach
@endsection
