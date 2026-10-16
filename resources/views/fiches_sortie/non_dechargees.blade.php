@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-time-five text-warning me-2"></i>Fiches de sortie non déchargées</h4>
      <span class="badge bg-warning fs-6">{{ $fiches->total() }} fiche(s) en attente</span>
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
        <form method="GET" action="{{ route('fiches_sortie.non_dechargees') }}">
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
              <label class="form-label">Date chargement début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Date chargement fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
            </div>
            <div class="col-md-9 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-search me-1"></i>Filtrer
              </button>
              <a href="{{ route('fiches_sortie.non_dechargees') }}" class="btn btn-outline-secondary">
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

        <table class="table table-hover">
          <thead class="table-warning">
            <tr>
              <th>Date chargement</th>
              <th>Véhicule</th>
              <th>Pont</th>
              <th>Agent</th>
              <th>Usine</th>
              <th>Produit</th>
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
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalDechargement{{ $f->id }}" title="Enregistrer déchargement">
                      <i class="bx bx-check-circle"></i> Décharger
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditFiche{{ $f->id }}" title="Modifier">
                      <i class="bx bx-edit"></i>
                    </button>
                    <a href="{{ route('fiches_sortie.show', ['fiche_id' => $f->id]) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                      <i class="bx bx-show"></i>
                    </a>
                    <a href="{{ route('fiches_sortie.pdf', ['fiche_id' => $f->id]) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Imprimer PDF">
                      <i class="bx bx-printer"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4">
                  <i class="bx bx-check-circle text-success fs-1"></i>
                  <p class="mt-2 mb-0">Aucune fiche en attente de déchargement</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($fiches->hasPages())
      <div class="mt-4 d-flex justify-content-center">
        {{ $fiches->links() }}
      </div>
    @endif
  </div>
</div>

@foreach($fiches as $f)
<!-- Modal Modification -->
<div class="modal fade" id="modalEditFiche{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Modifier la fiche — {{ $f->matricule_vehicule }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.update', ['fiche_id' => $f->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="row g-3">
            @php
              $ficheVehiculeId = (int) ($f->vehicule_id ?? 0);
              $ficheMatricule = trim((string) ($f->matricule_vehicule ?? ''));
              $fichePontId = (int) ($f->id_pont ?? 0);
              $ficheAgentId = (int) ($f->id_agent ?? 0);
              $ficheProduitId = (int) ($f->produit_id ?? 0);
              $ficheProduitNom = trim((string) ($f->nom_produit ?? ''));
              $vehiculeIds = collect($vehicules ?? [])->map(fn ($v) => (int) ($v['id_vehicule'] ?? 0))->all();
              $pontIds = collect($ponts ?? [])->map(fn ($p) => (int) ($p['id_pont'] ?? 0))->all();
              $agentIds = collect($agents ?? [])->map(fn ($a) => (int) ($a['id_agent'] ?? 0))->all();
              $produitIds = collect($produits ?? [])->map(fn ($p) => (int) $p->id)->all();
              $produitSelectionne = $ficheProduitId > 0
                ? $ficheProduitId
                : (int) (collect($produits ?? [])->first(fn ($p) => strcasecmp((string) $p->nom, $ficheProduitNom) === 0)?->id ?? 0);
            @endphp
            <div class="col-md-6">
              <label class="form-label">Camion <span class="text-danger">*</span></label>
              <select name="vehicule_id" class="form-select js-edit-vehicule js-select2-edit" data-placeholder="Rechercher un camion…" required>
                <option value="">-- Sélectionner un camion --</option>
                @if($ficheVehiculeId > 0 && ! in_array($ficheVehiculeId, $vehiculeIds, true))
                  <option value="{{ $ficheVehiculeId }}" data-matricule="{{ $ficheMatricule }}" selected>{{ $ficheMatricule !== '' ? $ficheMatricule : ('Véhicule #'.$ficheVehiculeId) }}</option>
                @endif
                @foreach($vehicules ?? [] as $v)
                  @php
                    $vid = (int) ($v['id_vehicule'] ?? 0);
                    $mat = trim((string) ($v['matricule_vehicule'] ?? ''));
                    $selected = ($vid > 0 && $vid === $ficheVehiculeId)
                      || ($mat !== '' && strcasecmp($mat, $ficheMatricule) === 0);
                  @endphp
                  <option value="{{ $vid }}" data-matricule="{{ $mat }}" {{ $selected ? 'selected' : '' }}>{{ $mat }}</option>
                @endforeach
              </select>
              <input type="hidden" name="matricule_vehicule" class="js-edit-matricule" value="{{ $ficheMatricule }}" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de chargement</label>
              <input type="date" name="date_chargement" class="form-control"
                value="{{ $f->date_chargement ? $f->date_chargement->format('Y-m-d') : '' }}" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Agent <span class="text-danger">*</span></label>
              <select name="id_agent" class="form-select js-edit-agent js-select2-edit" data-placeholder="Rechercher un agent…" required>
                <option value="">-- Sélectionner un agent --</option>
                @if($ficheAgentId > 0 && ! in_array($ficheAgentId, $agentIds, true))
                  <option value="{{ $ficheAgentId }}" selected>{{ $f->nom_agent }}{{ $f->numero_agent ? ' (' . $f->numero_agent . ')' : '' }}</option>
                @endif
                @foreach($agents ?? [] as $a)
                  @php
                    $nomComplet = trim((string) ($a['nom_complet'] ?? (($a['nom_agent'] ?? '') . ' ' . ($a['prenom_agent'] ?? ''))));
                    $numeroAgent = (string) ($a['numero_agent'] ?? '');
                    $isSelectedAgent = ((int) ($a['id_agent'] ?? 0) === $ficheAgentId)
                      || ($nomComplet !== '' && strcasecmp($nomComplet, trim((string) ($f->nom_agent ?? ''))) === 0);
                  @endphp
                  <option value="{{ $a['id_agent'] ?? '' }}" {{ $isSelectedAgent ? 'selected' : '' }}>
                    {{ $nomComplet }} ({{ $numeroAgent }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Pont de pesage <span class="text-danger">*</span></label>
              <select name="id_pont" class="form-select js-edit-pont js-select2-edit" data-placeholder="Rechercher un pont…"
                data-pont-selectionne="{{ $fichePontId }}"
                data-pont-label="{{ trim(($f->nom_pont ?? '').($f->code_pont ? ' ('.$f->code_pont.')' : '')) }}"
                required>
                <option value="">-- Sélectionner d'abord un agent --</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Produit <span class="text-danger">*</span></label>
              <select name="produit_id" class="form-select produit-select-fiche js-select2-edit" data-fiche="{{ $f->id }}" data-placeholder="Rechercher un produit…" required>
                <option value="">-- Sélectionner un produit --</option>
                @if($produitSelectionne > 0 && ! in_array($produitSelectionne, $produitIds, true))
                  <option value="{{ $produitSelectionne }}" selected>{{ $ficheProduitNom !== '' ? $ficheProduitNom : ('Produit #'.$produitSelectionne) }}</option>
                @endif
                @foreach($produits ?? [] as $produit)
                  <option value="{{ $produit->id }}" {{ (int) $produit->id === $produitSelectionne ? 'selected' : '' }}>
                    {{ $produit->nom }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Usine</label>
              <select name="usine" class="form-select usine-select-fiche js-select2-edit" data-fiche="{{ $f->id }}" data-usine-selectionnee="{{ $f->usine }}" data-placeholder="Rechercher une usine…" disabled>
                <option value="">-- Sélectionner d'abord un produit --</option>
                @if(trim((string) ($f->usine ?? '')) !== '')
                  <option value="{{ $f->usine }}" selected>{{ $f->usine }}</option>
                @endif
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Chef des chargeurs</label>
              <select name="id_chef_chargeur" class="form-select">
                <option value="">-- Sélectionner un chef --</option>
                @foreach($chefChargeurs ?? [] as $chef)
                  <option value="{{ $chef->id }}" {{ (int) ($f->id_chef_chargeur ?? 0) === (int) $chef->id ? 'selected' : '' }}>
                    {{ $chef->nom }} {{ $chef->prenoms }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Carburant (FCFA)</label>
              <input type="number" name="carburant" class="form-control" value="{{ $f->carburant }}" min="0" placeholder="Montant carburant" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Frais de route (FCFA)</label>
              <input type="number" name="frais_route" class="form-control" value="{{ $f->frais_route }}" min="0" placeholder="Frais de route" />
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

<!-- Modal Déchargement -->
<div class="modal fade" id="modalDechargement{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bx bx-check-circle me-2"></i>Enregistrer le déchargement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.dechargement', ['fiche_id' => $f->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Véhicule:</strong> {{ $f->matricule_vehicule }}<br>
            <strong>Date chargement:</strong> {{ $f->date_chargement ? $f->date_chargement->format('d/m/Y') : '-' }}<br>
            <strong>Pont:</strong> {{ $f->nom_pont }}<br>
            <strong class="text-danger">Usine:</strong> <span class="text-danger fw-bold">{{ $f->usine ?? '-' }}</span>
          </div>
          @php $pontGerable = (bool) ($gerableParPont[$f->id_pont] ?? false); @endphp
          @if(!$pontGerable)
            <div class="alert alert-secondary py-2 mb-3">
              <small>Pont non gérable — aucun parc requis, le déchargement n'impacte pas le stock.</small>
            </div>
          @endif
          @if($pontGerable)
          <div class="mb-3">
            <label class="form-label">Parc <span class="text-danger">*</span></label>
            <select name="parc_id" class="form-select" required>
              <option value="">-- Sélectionner un parc --</option>
              @php
                $parcsForPont = $parcsParPont[$f->id_pont] ?? collect();
              @endphp
              @foreach($parcsForPont as $parc)
                @php
                  $stockOuvert = \App\Models\Stock::where('parc_id', $parc->id)
                      ->where('type', 'entree')
                      ->where('statut', 'ouvert')
                      ->when($f->produit_id, fn($q) => $q->where('produit_id', $f->produit_id))
                      ->first();
                  $stockDispo = 0;
                  if ($stockOuvert) {
                    $sortiesStock = \App\Models\FicheSortie::where('stock_id', $stockOuvert->id)
                      ->whereNotNull('date_dechargement')
                      ->whereNotNull('poids_pont')
                      ->sum('poids_pont');
                    $stockDispo = max(0, (float) $stockOuvert->total_entrees - (float) $sortiesStock);
                  }
                  $selectedParc = (int) old('parc_id', $f->parc_id ?? 0) === (int) $parc->id;
                @endphp
                @if($stockOuvert)
                  <option value="{{ $parc->id }}" {{ $selectedParc ? 'selected' : '' }}>{{ $parc->nom }} (Stock dispo: {{ number_format($stockDispo, 0, ',', ' ') }} kg)</option>
                @else
                  <option value="{{ $parc->id }}" disabled>{{ $parc->nom }} (Pas de stock ouvert{{ $f->nom_produit ? ' pour ' . $f->nom_produit : '' }})</option>
                @endif
              @endforeach
            </select>
            @if($parcsForPont->isEmpty())
              <small class="text-danger">Aucun parc pour ce pont. <a href="{{ route('parcs.index') }}">Créer un parc</a></small>
            @endif
          </div>
          @endif
          <div class="mb-3">
            <label class="form-label">Date de déchargement <span class="text-danger">*</span></label>
            <input type="date" name="date_dechargement" class="form-control" value="{{ old('date_dechargement', date('Y-m-d')) }}" required />
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
          @if($f->nom_produit)
            <p class="mb-2"><strong>Produit:</strong> {{ $f->nom_produit }}</p>
          @endif
          <div class="mb-3">
            <label class="form-label">Poids (kg) <span class="text-danger">*</span></label>
            <input type="number" name="poids_pont" class="form-control" value="{{ old('poids_pont', $f->poids_pont) }}" placeholder="Poids en kg" min="0.01" step="0.01" required />
            @if($pontGerable)
              <small class="text-muted">Le poids sera déduit du stock ouvert du parc sélectionné.</small>
            @endif
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Confirmer le déchargement</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@endsection

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
  .select2-container { width: 100% !important; }
  .modal .select2-container { z-index: 1060; }
</style>
@endsection

@section('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var usinesParProduit = {!! json_encode($usinesParProduit ?? []) !!};
var agentsPontsMap = {!! json_encode($agentsPontsMap ?? []) !!};

function usinesPourProduit(produitId) {
  if (!produitId) return [];
  return usinesParProduit[produitId] || usinesParProduit[String(produitId)] || [];
}

function pontsPourAgent(agentId) {
  if (!agentId) return [];
  return agentsPontsMap[String(agentId)] || agentsPontsMap[agentId] || [];
}

function refreshSelect2(select) {
  if (window.jQuery && jQuery(select).hasClass('select2-hidden-accessible')) {
    jQuery(select).trigger('change.select2');
  }
}

function remplirSelectUsines(selectUsine, produitId, valeurSelectionnee) {
  if (!selectUsine) return;
  var liste = usinesPourProduit(produitId);
  var usineActuelle = valeurSelectionnee || selectUsine.dataset.usineSelectionnee || '';
  selectUsine.innerHTML = '<option value="">-- Sélectionner une usine --</option>';
  var trouvee = false;
  liste.forEach(function (u) {
    var opt = document.createElement('option');
    opt.value = u.nom || '';
    opt.textContent = u.code ? (u.nom + ' (' + u.code + ')') : (u.nom || '');
    if (usineActuelle && String(u.nom).toUpperCase() === String(usineActuelle).toUpperCase()) {
      opt.selected = true;
      trouvee = true;
    }
    selectUsine.appendChild(opt);
  });
  if (usineActuelle && !trouvee) {
    var keep = document.createElement('option');
    keep.value = usineActuelle;
    keep.textContent = usineActuelle;
    keep.selected = true;
    selectUsine.appendChild(keep);
  }
  if (!produitId) {
    selectUsine.disabled = true;
    selectUsine.options[0].textContent = '-- Sélectionner d\'abord un produit --';
  } else {
    selectUsine.disabled = false;
    if (liste.length === 0 && !usineActuelle) {
      selectUsine.options[0].textContent = '-- Aucune usine pour ce produit --';
    }
  }
  refreshSelect2(selectUsine);
}

function remplirSelectPonts(selectPont, agentId, garderSelection) {
  if (!selectPont) return;
  var ponts = pontsPourAgent(agentId);
  var pontActuel = garderSelection
    ? String(selectPont.dataset.pontSelectionne || selectPont.value || '')
    : '';
  var pontLabel = selectPont.dataset.pontLabel || '';

  selectPont.innerHTML = '<option value="">-- Sélectionner un pont --</option>';

  if (!agentId) {
    selectPont.options[0].textContent = '-- Sélectionner d\'abord un agent --';
    selectPont.value = '';
    refreshSelect2(selectPont);
    return;
  }

  var trouve = false;
  ponts.forEach(function (pont) {
    var opt = document.createElement('option');
    opt.value = pont.id;
    opt.textContent = pont.label;
    if (pontActuel && String(pont.id) === pontActuel) {
      opt.selected = true;
      trouve = true;
    }
    selectPont.appendChild(opt);
  });

  if (pontActuel && !trouve) {
    var keep = document.createElement('option');
    keep.value = pontActuel;
    keep.textContent = pontLabel || ('Pont #' + pontActuel);
    keep.selected = true;
    selectPont.appendChild(keep);
  } else if (!trouve && ponts.length === 1) {
    selectPont.value = String(ponts[0].id);
  } else if (!trouve && ponts.length === 0) {
    selectPont.options[0].textContent = '-- Aucun pont pour cet agent --';
  }

  refreshSelect2(selectPont);
}

function initUsineParProduit(produitSelect) {
  var ficheKey = produitSelect.dataset.fiche;
  var usineSelect = document.querySelector('.usine-select-fiche[data-fiche="' + ficheKey + '"]');
  if (!usineSelect) return;

  var usineInitiale = usineSelect.dataset.usineSelectionnee || '';
  remplirSelectUsines(usineSelect, produitSelect.value, usineInitiale);

  jQuery(produitSelect).on('change', function () {
    usineSelect.dataset.usineSelectionnee = '';
    remplirSelectUsines(usineSelect, this.value, '');
  });
}

function initSelect2Edit($select, $modal) {
  if (!$select.length || $select.hasClass('select2-hidden-accessible')) return;
  $select.select2({
    theme: 'bootstrap-5',
    width: '100%',
    dropdownParent: $modal,
    placeholder: $select.data('placeholder') || 'Rechercher…',
    allowClear: !$select.prop('required'),
    language: {
      noResults: function () { return 'Aucun résultat'; },
      searching: function () { return 'Recherche…'; }
    }
  });
}

$(function () {
  document.querySelectorAll('.produit-select-fiche').forEach(initUsineParProduit);

  document.querySelectorAll('.js-edit-agent').forEach(function (agentSelect) {
    var form = agentSelect.closest('form');
    var pontSelect = form ? form.querySelector('.js-edit-pont') : null;
    remplirSelectPonts(pontSelect, agentSelect.value, true);
  });

  $('.modal').on('shown.bs.modal', function () {
    var $modal = $(this);
    var agentSelect = $modal.find('.js-edit-agent')[0];
    var pontSelect = $modal.find('.js-edit-pont')[0];
    if (agentSelect && pontSelect) {
      remplirSelectPonts(pontSelect, agentSelect.value, true);
    }
    $modal.find('.js-select2-edit').each(function () {
      initSelect2Edit($(this), $modal);
    });
  });

  $(document).on('change', '.js-edit-agent', function () {
    var form = this.closest('form');
    var pontSelect = form ? form.querySelector('.js-edit-pont') : null;
    if (pontSelect) {
      pontSelect.dataset.pontSelectionne = '';
      remplirSelectPonts(pontSelect, this.value, false);
    }
  });

  $(document).on('change', '.js-edit-vehicule', function () {
    var option = this.options[this.selectedIndex];
    var hidden = $(this).closest('form').find('.js-edit-matricule');
    if (hidden.length) {
      hidden.val(option ? (option.getAttribute('data-matricule') || '') : '');
    }
  });

  $(document).on('submit', 'form', function () {
    $(this).find('.usine-select-fiche').prop('disabled', false);
  });

  var openModalId = @json(session('open_dechargement_modal'));
  if (openModalId) {
    var modalEl = document.getElementById('modalDechargement' + openModalId);
    if (modalEl && window.bootstrap) {
      new bootstrap.Modal(modalEl).show();
    }
  }
});
</script>
@endsection
