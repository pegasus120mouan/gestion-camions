@extends('layout.main')

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
  .modal .select2-container { z-index: 1056; }
  .transferts-ponts-table { font-size: 0.82rem; }
  .transferts-ponts-table th, .transferts-ponts-table td { white-space: nowrap; vertical-align: middle; }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y pt-2">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div>
        <h4 class="mb-1">Transfert d'un pont vers un autre</h4>
        <p class="text-muted mb-0">Débit du parc de provenance et crédit du parc de destination</p>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateTransfertPont">
        <i class="bx bx-transfer me-1"></i> Effectuer un transfert
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('transferts_ponts.index') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Véhicule, produit, pont, parc..." />
          </div>
          <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" value="{{ $dateDebut }}" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="{{ $dateFin }}" />
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('transferts_ponts.index') }}" class="btn btn-outline-secondary">Réinit.</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table transferts-ponts-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Véhicule</th>
              <th>Produit</th>
              <th>Pont provenance</th>
              <th>Parc source</th>
              <th class="text-end">Poids départ</th>
              <th>Pont destination</th>
              <th>Parc dest.</th>
              <th class="text-end">Poids arrivée</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($transferts as $t)
              <tr>
                <td>{{ $t->date_transfert?->format('d/m/Y') }}</td>
                <td><strong>{{ $t->matricule_vehicule }}</strong></td>
                <td><span class="badge bg-info">{{ $t->nom_produit ?: '—' }}</span></td>
                <td>{{ $t->nom_pont_source ?: '—' }}</td>
                <td>{{ $t->nom_parc_source ?: '—' }}</td>
                <td class="text-end text-danger fw-semibold">-{{ number_format((float) $t->poids_depart, 0, ',', ' ') }}</td>
                <td>{{ $t->nom_pont_destination ?: '—' }}</td>
                <td>{{ $t->nom_parc_destination ?: '—' }}</td>
                <td class="text-end text-success fw-semibold">+{{ number_format((float) $t->poids_arrivee, 0, ',', ' ') }}</td>
                <td class="text-end">
                  <form method="POST" action="{{ route('transferts_ponts.destroy', $t->id) }}" class="d-inline"
                        onsubmit="return confirm('Supprimer ce transfert {{ $t->matricule_vehicule }} ({{ $t->nom_pont_source }} → {{ $t->nom_pont_destination }}) ?\nLes mouvements de stock liés seront annulés.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer le transfert et ses mouvements de stock">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">Aucun transfert pont enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($transferts->hasPages())
        <div class="card-footer">{{ $transferts->links() }}</div>
      @endif
    </div>
  </div>
</div>

{{-- Modal Effectuer / Enregistrer un transfert --}}
<div class="modal fade" id="modalCreateTransfertPont" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('transferts_ponts.store') }}" id="formTransfertPontPage">
        @csrf
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white"><i class="bx bx-plus me-1"></i>Enregistrer un transfert</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Date <span class="text-danger">*</span></label>
              <input type="date" name="date_transfert" class="form-control" required value="{{ old('date_transfert', date('Y-m-d')) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Véhicule <span class="text-danger">*</span></label>
              <select name="matricule_vehicule" id="tp_vehicule" class="form-select" required data-placeholder="-- Choisir ou saisir --">
                <option value="">-- Choisir ou saisir --</option>
                @foreach($vehicules as $v)
                  <option value="{{ $v['matricule'] }}" data-vehicule-id="{{ $v['vehicule_id'] }}" @selected(old('matricule_vehicule') === $v['matricule'])}>
                    {{ $v['matricule'] }}
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="vehicule_id" id="tp_vehicule_id" value="{{ old('vehicule_id') }}">
              <div class="form-text">Tapez pour rechercher ou saisir un matricule.</div>
            </div>

            <div class="col-md-12">
              <label class="form-label">Produit <span class="text-danger">*</span></label>
              <select name="produit_id" id="tp_produit" class="form-select" required>
                <option value="">-- Choisir un produit --</option>
                @foreach($produits as $produit)
                  <option value="{{ $produit->id }}" @selected((string) old('produit_id') === (string) $produit->id)>{{ $produit->nom }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12"><hr class="my-1"><div class="fw-semibold text-primary">Provenance</div></div>

            <div class="col-md-6">
              <label class="form-label">Pont de provenance <span class="text-danger">*</span></label>
              <select name="id_pont_source" id="tp_pont_source" class="form-select" required>
                <option value="">-- Choisir un pont --</option>
                @foreach($ponts as $pont)
                  <option value="{{ $pont['id'] }}"
                    data-nom="{{ $pont['nom'] }}"
                    data-code="{{ $pont['code'] }}"
                    data-gerable="{{ !empty($pont['gerable']) ? '1' : '0' }}"
                    @selected((string) old('id_pont_source') === (string) $pont['id'])>
                    {{ $pont['label'] }}
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="nom_pont_source" id="tp_nom_pont_source" value="{{ old('nom_pont_source') }}">
              <input type="hidden" name="code_pont_source" id="tp_code_pont_source" value="{{ old('code_pont_source') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Parc source <span class="text-danger" id="tp_parc_source_req">*</span></label>
              <select name="parc_source_id" id="tp_parc_source" class="form-select">
                <option value="">-- Choisir un pont --</option>
              </select>
              <input type="hidden" name="stock_source_id" id="tp_stock_source_id" value="{{ old('stock_source_id') }}">
              <small class="text-muted" id="tp_stock_source_hint">Obligatoire seulement si le pont est gérable. Sinon le transfert reste possible sans parc.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Poids départ (kg) <span class="text-danger">*</span></label>
              <input type="number" name="poids_depart" id="tp_poids_depart" class="form-control" min="0.01" step="0.01" required value="{{ old('poids_depart') }}" placeholder="Ex: 25000">
              <small class="text-muted" id="tp_poids_depart_hint">Déduit du parc de provenance si un stock est ouvert.</small>
            </div>

            <div class="col-12"><hr class="my-1"><div class="fw-semibold text-success">Destination</div></div>

            <div class="col-md-6">
              <label class="form-label">Pont de destination <span class="text-danger">*</span></label>
              <select name="id_pont_destination" id="tp_pont_destination" class="form-select" required>
                <option value="">-- Choisir un pont --</option>
                @foreach($ponts as $pont)
                  <option value="{{ $pont['id'] }}"
                    data-nom="{{ $pont['nom'] }}"
                    data-code="{{ $pont['code'] }}"
                    data-gerable="{{ !empty($pont['gerable']) ? '1' : '0' }}"
                    @selected((string) old('id_pont_destination') === (string) $pont['id'])>
                    {{ $pont['label'] }}
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="nom_pont_destination" id="tp_nom_pont_destination" value="{{ old('nom_pont_destination') }}">
              <input type="hidden" name="code_pont_destination" id="tp_code_pont_destination" value="{{ old('code_pont_destination') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Parc / stock destination <span class="text-danger">*</span></label>
              <select name="stock_destination_id" id="tp_stock_destination" class="form-select" required>
                <option value="">-- Choisir pont et produit --</option>
              </select>
              <small class="text-muted">Le poids d’arrivée sera crédité sur ce parc.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Poids arrivée (kg) <span class="text-danger">*</span></label>
              <input type="number" name="poids_arrivee" id="tp_poids_arrivee" class="form-control" min="0.01" step="0.01" required value="{{ old('poids_arrivee') }}" placeholder="Ex: 25000">
              <small class="text-muted">Crédité au parc de destination.</small>
            </div>
            <div class="col-md-12">
              <label class="form-label">Commentaire</label>
              <input type="text" name="commentaire" class="form-control" maxlength="500" value="{{ old('commentaire') }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i>Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var stocksUrl = @json(route('transferts_ponts.stocks_pont_produit'));
  var parcsParPont = @json($parcsParPont ?? []);
  var produitSelect = document.getElementById('tp_produit');
  var pontSource = document.getElementById('tp_pont_source');
  var pontDest = document.getElementById('tp_pont_destination');
  var parcSource = document.getElementById('tp_parc_source');
  var parcSourceReq = document.getElementById('tp_parc_source_req');
  var stockSourceId = document.getElementById('tp_stock_source_id');
  var stockDest = document.getElementById('tp_stock_destination');
  var sourceHint = document.getElementById('tp_stock_source_hint');
  var poidsDepart = document.getElementById('tp_poids_depart');
  var poidsArrivee = document.getElementById('tp_poids_arrivee');
  var poidsHint = document.getElementById('tp_poids_depart_hint');
  var vehiculeSelect = document.getElementById('tp_vehicule');
  var vehiculeId = document.getElementById('tp_vehicule_id');
  var stocksSourceCache = [];

  function formatKg(n) {
    return Math.round(n || 0).toLocaleString('fr-FR');
  }

  function pontSourceGerable() {
    var opt = pontSource.options[pontSource.selectedIndex];
    return !!(opt && opt.value && opt.dataset.gerable === '1');
  }

  function syncPontHidden(selectEl, nomHiddenId, codeHiddenId) {
    var opt = selectEl.options[selectEl.selectedIndex];
    var nom = document.getElementById(nomHiddenId);
    var code = document.getElementById(codeHiddenId);
    if (nom) nom.value = opt && opt.dataset.nom ? opt.dataset.nom : '';
    if (code) code.value = opt && opt.dataset.code ? opt.dataset.code : '';
  }

  function setParcSourceRequired(required) {
    if (required) {
      parcSource.setAttribute('required', 'required');
      if (parcSourceReq) parcSourceReq.classList.remove('d-none');
    } else {
      parcSource.removeAttribute('required');
      if (parcSourceReq) parcSourceReq.classList.add('d-none');
    }
  }

  function remplirParcsSource(idPont) {
    parcSource.innerHTML = '<option value="">-- Sélectionner un parc --</option>';
    stockSourceId.value = '';
    poidsDepart.removeAttribute('max');
    setParcSourceRequired(false);

    if (!idPont) {
      parcSource.innerHTML = '<option value="">-- Choisir un pont --</option>';
      if (sourceHint) sourceHint.textContent = 'Obligatoire seulement si le pont est gérable. Sinon le transfert reste possible sans parc.';
      return;
    }

    var gerable = pontSourceGerable();
    var parcs = parcsParPont[String(idPont)] || parcsParPont[idPont] || [];

    if (!gerable) {
      parcSource.innerHTML = '<option value="">Pont non gérable — parc non requis</option>';
      setParcSourceRequired(false);
      if (sourceHint) sourceHint.textContent = 'Pont non gérable : transfert possible sans parc source (pas de débit).';
      if (poidsHint) poidsHint.textContent = 'Aucun débit à la provenance (pont non gérable).';
      return;
    }

    if (!parcs.length) {
      parcSource.innerHTML = '<option value="">Aucun parc actif — transfert sans parc</option>';
      setParcSourceRequired(false);
      if (sourceHint) sourceHint.textContent = 'Aucun parc actif : transfert autorisé sans débit.';
      if (poidsHint) poidsHint.textContent = 'Pas de parc source — aucun débit à la provenance.';
      return;
    }

    setParcSourceRequired(true);
    if (sourceHint) sourceHint.textContent = 'Si un stock est ouvert, le poids sera déduit ; sinon le transfert reste possible.';
    parcs.forEach(function (parc) {
      var opt = document.createElement('option');
      opt.value = parc.id;
      opt.dataset.nom = parc.nom || 'Parc';
      opt.textContent = parc.nom || 'Parc';
      parcSource.appendChild(opt);
    });
    if (parcs.length === 1) {
      parcSource.value = String(parcs[0].id);
    }
    lierStockSource();
  }

  function chargerStocks(idPont, selectEl) {
    selectEl.innerHTML = '<option value="">Chargement…</option>';
    var produitId = produitSelect.value;
    if (!idPont || !produitId) {
      selectEl.innerHTML = '<option value="">-- Choisir pont et produit --</option>';
      return Promise.resolve([]);
    }
    var params = new URLSearchParams({ id_pont: idPont, produit_id: produitId });
    return fetch(stocksUrl + '?' + params.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        selectEl.innerHTML = '<option value="">-- Sélectionner un parc --</option>';
        (data.stocks || []).forEach(function (s) {
          var opt = document.createElement('option');
          opt.value = s.stock_id;
          opt.dataset.disponible = s.stock_actuel_chaine != null ? s.stock_actuel_chaine : s.disponible;
          opt.dataset.nomParc = s.nom_parc || 'Parc';
          opt.textContent = (s.nom_parc || 'Parc') + ' — entrées ' + formatKg(s.total_entrees || 0) + ' kg';
          selectEl.appendChild(opt);
        });
        if (!(data.stocks || []).length) {
          selectEl.innerHTML = '<option value="">Aucun stock ouvert pour ce produit</option>';
        } else if (data.stocks.length === 1) {
          selectEl.value = String(data.stocks[0].stock_id);
        }
        return data.stocks || [];
      })
      .catch(function () {
        selectEl.innerHTML = '<option value="">Erreur de chargement</option>';
        return [];
      });
  }

  function chargerStocksSource() {
    var idPont = pontSource.value;
    var produitId = produitSelect.value;
    stocksSourceCache = [];
    stockSourceId.value = '';
    if (!idPont || !produitId) {
      lierStockSource();
      return;
    }
    var params = new URLSearchParams({ id_pont: idPont, produit_id: produitId });
    fetch(stocksUrl + '?' + params.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        stocksSourceCache = data.stocks || [];
        lierStockSource();
      })
      .catch(function () {
        stocksSourceCache = [];
        lierStockSource();
      });
  }

  function lierStockSource() {
    var parcId = parcSource.value;
    stockSourceId.value = '';
    poidsDepart.removeAttribute('max');
    if (!parcId) {
      if (sourceHint) sourceHint.textContent = 'Si un stock est ouvert, le poids sera déduit ; sinon le transfert reste possible.';
      if (poidsHint) poidsHint.textContent = 'Déduit du parc de provenance si un stock est ouvert.';
      return;
    }
    var match = stocksSourceCache.find(function (s) {
      return String(s.parc_id || '') === String(parcId);
    });
    if (match) {
      stockSourceId.value = String(match.stock_id);
      var dispo = match.stock_actuel_chaine != null ? match.stock_actuel_chaine : match.disponible;
      poidsDepart.max = String(dispo);
      if (sourceHint) {
        sourceHint.textContent = 'Stock ouvert — actuel ' + formatKg(dispo) + ' kg (sera déduit).';
      }
      if (poidsHint) {
        poidsHint.textContent = 'Maximum : ' + formatKg(dispo) + ' kg — déduit du parc de provenance.';
      }
    } else {
      if (sourceHint) {
        sourceHint.textContent = 'Aucun stock ouvert sur ce parc : transfert autorisé sans débit.';
      }
      if (poidsHint) {
        poidsHint.textContent = 'Pas de stock ouvert — aucun débit à la provenance.';
      }
    }
  }

  if (window.jQuery) {
    $('#tp_vehicule').select2({
      theme: 'bootstrap-5',
      dropdownParent: $('#modalCreateTransfertPont'),
      width: '100%',
      tags: true,
      placeholder: '-- Choisir ou saisir --'
    }).on('change', function () {
      var opt = this.options[this.selectedIndex];
      vehiculeId.value = opt && opt.dataset.vehiculeId ? opt.dataset.vehiculeId : '';
    });
  } else if (vehiculeSelect) {
    vehiculeSelect.addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      vehiculeId.value = opt && opt.dataset.vehiculeId ? opt.dataset.vehiculeId : '';
    });
  }

  produitSelect.addEventListener('change', function () {
    chargerStocksSource();
    chargerStocks(pontDest.value, stockDest);
  });

  pontSource.addEventListener('change', function () {
    syncPontHidden(pontSource, 'tp_nom_pont_source', 'tp_code_pont_source');
    remplirParcsSource(this.value);
    chargerStocksSource();
  });

  pontDest.addEventListener('change', function () {
    syncPontHidden(pontDest, 'tp_nom_pont_destination', 'tp_code_pont_destination');
    chargerStocks(this.value, stockDest);
  });

  parcSource.addEventListener('change', lierStockSource);

  poidsDepart.addEventListener('input', function () {
    if (!poidsArrivee.value) {
      poidsArrivee.value = this.value;
    }
  });

  document.getElementById('formTransfertPontPage').addEventListener('submit', function (e) {
    if (pontSource.value && pontDest.value && String(pontSource.value) === String(pontDest.value)) {
      e.preventDefault();
      alert('Le pont de destination doit être différent du pont de provenance.');
      return;
    }
    // Pont gérable avec parcs : parc source obligatoire. Sinon (non gérable) OK sans parc.
    if (parcSource && parcSource.hasAttribute('required') && !parcSource.value) {
      e.preventDefault();
      alert('Sélectionnez un parc source.');
      return;
    }
    var dispo = stockSourceId.value ? parseFloat(poidsDepart.max || '') : NaN;
    var poids = parseFloat(poidsDepart.value || '');
    if (stockSourceId.value && !isNaN(dispo) && poids > dispo + 0.0001) {
      e.preventDefault();
      alert('Le poids départ dépasse le stock actuel (' + formatKg(dispo) + ' kg).');
    }
  });

  @if($errors->any())
    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreateTransfertPont'));
    modal.show();
    if (pontSource.value) {
      syncPontHidden(pontSource, 'tp_nom_pont_source', 'tp_code_pont_source');
      remplirParcsSource(pontSource.value);
      @if(old('parc_source_id'))
        parcSource.value = @json((string) old('parc_source_id'));
      @endif
      chargerStocksSource();
    }
    if (pontDest.value) {
      syncPontHidden(pontDest, 'tp_nom_pont_destination', 'tp_code_pont_destination');
      var oldStockDest = @json(old('stock_destination_id') ? (string) old('stock_destination_id') : '');
      chargerStocks(pontDest.value, stockDest).then(function () {
        if (oldStockDest) {
          stockDest.value = oldStockDest;
        }
      });
    }
  @endif
});
</script>
@endsection
