@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des fiches de sortie</h4>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- Filtres de recherche -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-filter-alt me-2"></i>Filtres de recherche</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('fiches_sortie.index') }}">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Véhicule</label>
              <select name="vehicule" class="form-select">
                <option value="">Tous les véhicules</option>
                @foreach($vehicules ?? [] as $v)
                  <option value="{{ $v['matricule_vehicule'] ?? '' }}" {{ request('vehicule') == ($v['matricule_vehicule'] ?? '') ? 'selected' : '' }}>
                    {{ $v['matricule_vehicule'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Pont</label>
              <select name="pont" class="form-select">
                <option value="">Tous les ponts</option>
                @foreach($ponts ?? [] as $p)
                  <option value="{{ $p['nom_pont'] ?? '' }}" {{ request('pont') == ($p['nom_pont'] ?? '') ? 'selected' : '' }}>
                    {{ $p['nom_pont'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Usine</label>
              <select name="usine" class="form-select">
                <option value="">Toutes les usines</option>
                @foreach($usines ?? [] as $u)
                  <option value="{{ $u['nom_usine'] ?? '' }}" {{ request('usine') == ($u['nom_usine'] ?? '') ? 'selected' : '' }}>
                    {{ $u['nom_usine'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Chef Chargeur</label>
              <select name="chef_chargeur" class="form-select">
                <option value="">Tous les chefs chargeurs</option>
                @foreach($chefChargeurs ?? [] as $cc)
                  <option value="{{ $cc->id }}" {{ request('chef_chargeur') == $cc->id ? 'selected' : '' }}>
                    {{ $cc->nom }} {{ $cc->prenoms }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Type de date</label>
              <select name="type_date" class="form-select">
                <option value="chargement" {{ request('type_date', 'chargement') == 'chargement' ? 'selected' : '' }}>Date chargement</option>
                <option value="dechargement" {{ request('type_date') == 'dechargement' ? 'selected' : '' }}>Date déchargement</option>
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
            <div class="col-md-6 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-search me-1"></i>Filtrer
              </button>
              <a href="{{ route('fiches_sortie.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-refresh me-1"></i>Réinitialiser
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Date chargement</th>
              <th>Vehicule</th>
              <th>Pont</th>
              <th>Agent</th>
              <th>Usine</th>
              <th>Produit</th>
              <th>Date déchargement</th>
              <th>N° ticket</th>
              <th>Poids (kg)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fiches as $f)
              <tr>
                <td>{{ $f->date_chargement ? $f->date_chargement->format('d-m-Y') : '-' }}</td>
                <td>
                  <a href="#" data-bs-toggle="modal" data-bs-target="#modalDechargement{{ $f->id }}" class="text-primary text-decoration-none">
                    <strong>{{ $f->matricule_vehicule }}</strong>
                  </a>
                </td>
                <td>{{ $f->nom_pont }}</td>
                <td>{{ $f->nom_agent }}</td>
                <td>{{ $f->usine ?? '-' }}</td>
                <td>
                  @if($f->nom_produit)
                    <span class="badge bg-info">{{ $f->nom_produit }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($f->date_dechargement)
                    {{ $f->date_dechargement->format('d-m-Y') }}
                  @else
                    <span class="text-danger">Pas encore déchargé</span>
                  @endif
                </td>
                <td>
                  @if($f->numero_ticket)
                    <span class="fw-medium">{{ $f->numero_ticket }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($f->poids_pont)
                    {{ number_format((float)$f->poids_pont, 0, ',', ' ') }}
                  @else
                    <span class="text-warning">Poids pas encore renseigné</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="{{ route('fiches_sortie.show', ['fiche_id' => $f->id]) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                      <i class="bx bx-show"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditFiche{{ $f->id }}" title="Modifier">
                      <i class="bx bx-edit"></i>
                    </button>
                    <a href="{{ route('fiches_sortie.pdf', ['fiche_id' => $f->id]) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Imprimer PDF">
                      <i class="bx bx-printer"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteFiche{{ $f->id }}" title="Supprimer">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center">Aucune fiche de sortie</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($fiches->count() > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Resume</h5>
          <p><strong>Total fiches:</strong> {{ $fiches->total() }}</p>
        </div>
      </div>
    @endif

    @if($fiches->hasPages())
      <div class="mt-4 d-flex justify-content-center">
        {{ $fiches->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Modal Ajouter une fiche de sortie -->
<div class="modal fade" id="modalAddFicheSortie" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bx bx-plus-circle me-2"></i>Ajouter une fiche de sortie</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.store') }}">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Véhicule <span class="text-danger">*</span></label>
              <select name="vehicule_id" id="selectVehicule" class="form-select" required>
                <option value="">-- Sélectionner un véhicule --</option>
                @foreach($vehicules as $v)
                  <option value="{{ $v['id_vehicule'] ?? '' }}" data-matricule="{{ $v['matricule_vehicule'] ?? '' }}">
                    {{ $v['matricule_vehicule'] ?? '' }}
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="matricule_vehicule" id="hiddenMatricule" value="" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Pont de pesage <span class="text-danger">*</span></label>
              <select name="id_pont" id="selectPont" class="form-select" required>
                <option value="">-- Sélectionner un pont --</option>
                @foreach($ponts as $p)
                  <option value="{{ $p['id_pont'] ?? '' }}" data-display="{{ ($p['nom_pont'] ?? '') . ' (' . ($p['code_pont'] ?? '') . ')' }}">
                    {{ $p['nom_pont'] ?? '' }} ({{ $p['code_pont'] ?? '' }})
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="pont_display" id="hiddenPontDisplay" value="" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Agent <span class="text-danger">*</span></label>
              <select name="id_agent" id="selectAgent" class="form-select" required>
                <option value="">-- Sélectionner un agent --</option>
                @foreach($agents as $a)
                  @php
                    $nomComplet = $a['nom_complet'] ?? (($a['nom_agent'] ?? '') . ' ' . ($a['prenom_agent'] ?? ''));
                    $numeroAgent = $a['numero_agent'] ?? '';
                  @endphp
                  <option value="{{ $a['id_agent'] ?? '' }}" data-display="{{ $nomComplet . ' (' . $numeroAgent . ')' }}">
                    {{ $nomComplet }} ({{ $numeroAgent }})
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="agent_display" id="hiddenAgentDisplay" value="" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Usine</label>
              <select name="usine" class="form-select">
                <option value="">-- Sélectionner une usine --</option>
                @foreach($usines ?? [] as $u)
                  <option value="{{ $u['nom_usine'] ?? '' }}">{{ $u['nom_usine'] ?? '' }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de chargement <span class="text-danger">*</span></label>
              <input type="date" name="date_chargement" class="form-control" value="{{ date('Y-m-d') }}" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de déchargement</label>
              <input type="date" name="date_dechargement" class="form-control" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach($fiches as $f)
<!-- Modal Édition Fiche -->
<div class="modal fade" id="modalEditFiche{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Modifier la fiche de sortie</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.update', ['fiche_id' => $f->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Pont de pesage <span class="text-danger">*</span></label>
              <select name="id_pont" class="form-select" required>
                <option value="">-- Sélectionner un pont --</option>
                @foreach($ponts as $p)
                  @php
                    $pontNom = trim($p['nom_pont'] ?? '');
                    $ficheNomPont = trim($f->nom_pont ?? '');
                    $isSelectedPont = ($f->id_pont == ($p['id_pont'] ?? '')) || (strtolower($pontNom) == strtolower($ficheNomPont));
                  @endphp
                  <option value="{{ $p['id_pont'] ?? '' }}" data-nom="{{ $pontNom }}" data-code="{{ $p['code_pont'] ?? '' }}" {{ $isSelectedPont ? 'selected' : '' }}>
                    {{ $pontNom }} ({{ $p['code_pont'] ?? '' }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Agent <span class="text-danger">*</span></label>
              <select name="id_agent" class="form-select" required>
                <option value="">-- Sélectionner un agent --</option>
                @foreach($agents as $a)
                  @php
                    $nomComplet = $a['nom_complet'] ?? (($a['nom_agent'] ?? '') . ' ' . ($a['prenom_agent'] ?? ''));
                    $numeroAgent = $a['numero_agent'] ?? '';
                    $ficheNomAgent = trim($f->nom_agent ?? '');
                    $isSelectedAgent = ($f->id_agent == ($a['id_agent'] ?? '')) || (strtolower(trim($nomComplet)) == strtolower($ficheNomAgent));
                  @endphp
                  <option value="{{ $a['id_agent'] ?? '' }}" data-nom="{{ $nomComplet }}" data-numero="{{ $numeroAgent }}" {{ $isSelectedAgent ? 'selected' : '' }}>
                    {{ $nomComplet }} ({{ $numeroAgent }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Usine</label>
              <select name="usine" class="form-select">
                <option value="">-- Sélectionner une usine --</option>
                @foreach($usines ?? [] as $u)
                  <option value="{{ $u['nom_usine'] ?? '' }}" {{ ($f->usine == ($u['nom_usine'] ?? '')) ? 'selected' : '' }}>
                    {{ $u['nom_usine'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Chef des chargeurs</label>
              <select name="id_chef_chargeur" class="form-select">
                <option value="">-- Sélectionner un chef --</option>
                @foreach($chefChargeurs ?? [] as $chef)
                  <option value="{{ $chef->id }}" {{ ($f->id_chef_chargeur == $chef->id) ? 'selected' : '' }}>
                    {{ $chef->nom }} {{ $chef->prenoms }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de déchargement</label>
              <input type="date" name="date_dechargement" class="form-control" value="{{ $f->date_dechargement ? $f->date_dechargement->format('Y-m-d') : '' }}" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Poids (kg)</label>
              <input type="number" name="poids_pont" class="form-control" value="{{ $f->poids_pont }}" step="0.01" placeholder="Poids en kg" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Carburant (FCFA)</label>
              <input type="number" name="carburant" class="form-control" value="{{ $f->carburant }}" placeholder="Montant carburant" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Frais de route (FCFA)</label>
              <input type="number" name="frais_route" class="form-control" value="{{ $f->frais_route }}" placeholder="Frais de route" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Confirmation Suppression -->
<div class="modal fade" id="modalDeleteFiche{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-error-circle me-2"></i>Confirmation de suppression</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="bx bx-trash text-danger" style="font-size: 4rem;"></i>
        <h5 class="mt-3">Supprimer cette fiche de sortie ?</h5>
        <p class="text-muted mb-0">
          Véhicule: <strong>{{ $f->matricule_vehicule }}</strong><br>
          Pont: {{ $f->nom_pont }}<br>
          Date: {{ $f->date_chargement->format('d/m/Y') }}
        </p>
        <div class="alert alert-warning mt-3 mb-0">
          <i class="bx bx-info-circle me-1"></i>
          Cette action est irréversible.
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i>Annuler
        </button>
        <form action="{{ route('fiches_sortie.destroy', ['fiche_id' => $f->id]) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">
            <i class="bx bx-trash me-1"></i>Supprimer
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Déchargement -->
<div class="modal fade" id="modalDechargement{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="bx bx-package me-2"></i>Déchargement - {{ $f->matricule_vehicule }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.dechargement', ['fiche_id' => $f->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @php
            $stockFiche = $f->stock_id ? \App\Models\Stock::find($f->stock_id) : null;
            if (!$stockFiche && $f->parc_id && $f->produit_id) {
              $stockFiche = \App\Models\Stock::where('parc_id', $f->parc_id)
                ->where('produit_id', $f->produit_id)
                ->where('type', 'entree')
                ->where('statut', 'ouvert')
                ->first();
            }
            $stockDispoFiche = 0;
            if ($stockFiche) {
              $sortiesStock = \App\Models\FicheSortie::where('stock_id', $stockFiche->id)
                ->whereNotNull('date_dechargement')
                ->whereNotNull('poids_pont')
                ->when($f->date_dechargement, fn($q) => $q->where('id', '!=', $f->id))
                ->sum('poids_pont');
              $stockDispoFiche = max(0, (float) $stockFiche->total_entrees - (float) $sortiesStock);
            }
          @endphp
          <div class="mb-3">
            <p><strong>Date de chargement:</strong> {{ $f->date_chargement ? $f->date_chargement->format('d/m/Y') : '-' }}</p>
            <p><strong>Pont:</strong> {{ $f->nom_pont ?? '-' }}</p>
            <p><strong>Agent:</strong> {{ $f->nom_agent ?? '-' }}</p>
            @if($f->nom_parc)
              <p><strong>Parc:</strong> {{ $f->nom_parc }}</p>
            @endif
            @if($f->nom_produit)
              <p><strong>Produit:</strong> {{ $f->nom_produit }}</p>
            @endif
            @if($stockFiche)
              <p class="mb-0"><strong>Stock disponible:</strong>
                <span class="text-success fw-bold">{{ number_format($stockDispoFiche, 0, ',', ' ') }} kg</span>
              </p>
            @endif
          </div>
          <div class="mb-3">
            <label class="form-label">Parc <span class="text-danger">*</span></label>
            <select name="parc_id" class="form-select @error('parc_id') is-invalid @enderror" required>
              <option value="">-- Sélectionner un parc --</option>
              @php
                $parcsForPont = $parcsParPont[$f->id_pont] ?? collect();
              @endphp
              @foreach($parcsForPont as $parc)
                @php
                  $stockOuvertParc = \App\Models\Stock::where('parc_id', $parc->id)
                      ->where('type', 'entree')
                      ->where('statut', 'ouvert')
                      ->when($f->produit_id, fn($q) => $q->where('produit_id', $f->produit_id))
                      ->first();
                  $selectedParc = (int) old('parc_id', $f->parc_id ?? 0) === (int) $parc->id;
                @endphp
                @if($stockOuvertParc)
                  <option value="{{ $parc->id }}" @selected($selectedParc)>{{ $parc->nom }}</option>
                @else
                  <option value="{{ $parc->id }}" disabled>{{ $parc->nom }} (pas de stock ouvert{{ $f->nom_produit ? ' pour ' . $f->nom_produit : '' }})</option>
                @endif
              @endforeach
            </select>
            @error('parc_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if($parcsForPont->isEmpty())
              <small class="text-danger">Aucun parc pour ce pont. <a href="{{ route('parcs.index') }}">Créer un parc</a></small>
            @endif
          </div>
          <hr>
          <div class="mb-3">
            <label class="form-label">Date de déchargement <span class="text-danger">*</span></label>
            <input type="date" name="date_dechargement" class="form-control" value="{{ old('date_dechargement', $f->date_dechargement ? $f->date_dechargement->format('Y-m-d') : date('Y-m-d')) }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">N° ticket <span class="text-danger">*</span></label>
            <input type="text" name="numero_ticket" class="form-control @error('numero_ticket') is-invalid @enderror"
              value="{{ old('numero_ticket', $f->numero_ticket ?? '') }}" placeholder="Numéro du ticket" maxlength="100" required />
            @error('numero_ticket')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Obligatoire et unique — un numéro déjà utilisé sera refusé.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Poids (kg) <span class="text-danger">*</span></label>
            <input type="number" name="poids_pont" id="poids_pont_{{ $f->id }}" class="form-control" value="{{ old('poids_pont', $f->poids_pont ?? '') }}" placeholder="Poids en kg" min="0.01" step="0.01" required onchange="calculerMontantCamion({{ $f->id }})" oninput="calculerMontantCamion({{ $f->id }})">
            @if($stockFiche)
              <small class="text-muted">Le poids sera déduit du stock du parc {{ $f->nom_parc ?? $stockFiche->nom_parc }}. Si le déchargement dépasse le stock disponible, l'écart est enregistré comme gain.</small>
            @endif
          </div>
          <div class="mb-3">
            <label class="form-label">Prix unitaire (FCFA/kg)</label>
            <input type="number" name="prix_unitaire_camion" id="prix_unitaire_camion_{{ $f->id }}" class="form-control" value="{{ old('prix_unitaire_camion', $f->prix_unitaire_camion ?? '') }}" placeholder="Ex: 150" min="0" step="1" onchange="calculerMontantCamion({{ $f->id }})" oninput="calculerMontantCamion({{ $f->id }})">
          </div>
          <div class="mb-3">
            <label class="form-label">Montant camion</label>
            <div class="input-group">
              <input type="text" id="montant_camion_display_{{ $f->id }}" class="form-control fw-bold text-success" style="background-color: #e9ecef;" readonly placeholder="0">
              <span class="input-group-text">FCFA</span>
            </div>
            <input type="hidden" name="montant_camion" id="montant_camion_{{ $f->id }}" value="{{ $f->montant_camion ?? 0 }}">
            <small class="text-muted">Montant = Prix unitaire × Poids</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-info"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectVehicule = document.getElementById('selectVehicule');
  const hiddenMatricule = document.getElementById('hiddenMatricule');
  const selectPont = document.getElementById('selectPont');
  const hiddenPontDisplay = document.getElementById('hiddenPontDisplay');
  const selectAgent = document.getElementById('selectAgent');
  const hiddenAgentDisplay = document.getElementById('hiddenAgentDisplay');
  const form = document.querySelector('#modalAddFicheSortie form');

  function updateHiddenFields() {
    if (selectVehicule) {
      const selectedV = selectVehicule.options[selectVehicule.selectedIndex];
      hiddenMatricule.value = selectedV.dataset.matricule || '';
    }
    if (selectPont) {
      const selectedP = selectPont.options[selectPont.selectedIndex];
      hiddenPontDisplay.value = selectedP.dataset.display || '';
    }
    if (selectAgent) {
      const selectedA = selectAgent.options[selectAgent.selectedIndex];
      hiddenAgentDisplay.value = selectedA.dataset.display || '';
    }
  }

  if (selectVehicule) {
    selectVehicule.addEventListener('change', updateHiddenFields);
  }
  if (selectPont) {
    selectPont.addEventListener('change', updateHiddenFields);
  }
  if (selectAgent) {
    selectAgent.addEventListener('change', updateHiddenFields);
  }

  if (form) {
    form.addEventListener('submit', function(e) {
      updateHiddenFields();
    });
  }
});

// Fonction pour calculer le montant camion
function calculerMontantCamion(ficheId) {
  var poids = parseFloat(document.getElementById('poids_pont_' + ficheId).value) || 0;
  var prixUnitaire = parseFloat(document.getElementById('prix_unitaire_camion_' + ficheId).value) || 0;
  var montant = poids * prixUnitaire;
  
  document.getElementById('montant_camion_' + ficheId).value = montant;
  document.getElementById('montant_camion_display_' + ficheId).value = montant.toLocaleString('fr-FR').replace(/,/g, ' ');
}

document.addEventListener('DOMContentLoaded', function () {
  var openModalId = @json(session('open_dechargement_modal'));
  if (openModalId) {
    var modalEl = document.getElementById('modalDechargement' + openModalId);
    if (modalEl && window.bootstrap) {
      new bootstrap.Modal(modalEl).show();
    }
  }

  @foreach($fiches as $f)
  calculerMontantCamion({{ $f->id }});
  @endforeach
});
</script>
@endsection
