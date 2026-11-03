@extends('layout.main')
@section('content')
@php
  $enCours = $fiche->groupeEnCours();
  $localisation = $fiche->localisation_type === 'parc'
    ? ('Parc #' . ($fiche->localisation_parc_id ?? '—'))
    : ($fiche->localisation_type === 'transit' ? 'En transit (camion)' : '—');
@endphp
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    @include('sorties_groupees._onglets', ['onglet' => 'fiches'])

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
      <div>
        <h4 class="mb-1">Fiche groupée {{ $fiche->numero_fiche }}</h4>
        <p class="text-muted mb-0">
          {{ $fiche->matricule_vehicule }}
          · {{ $fiche->nom_produit ?? '—' }}
          · {{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }} kg
        </p>
      </div>
      <div class="d-flex gap-2">
        @if($enCours)
          <span class="badge bg-label-warning align-self-center">En cours</span>
        @else
          <span class="badge bg-label-success align-self-center">Clôturée</span>
        @endif
        <a href="{{ route('sorties_groupees.index') }}" class="btn btn-outline-secondary">
          <i class="bx bx-list-ul me-1"></i>Liste des fiches
        </a>
        <a href="{{ route('sorties_groupees.depenses', ['vehicule_id' => $fiche->vehicule_id, 'matricule' => $fiche->matricule_vehicule]) }}" class="btn btn-outline-secondary">
          Véhicule
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="text-muted small">Agent actuel</div>
            <div class="fw-semibold">{{ $fiche->nom_agent ?: '—' }}</div>
            <small class="text-muted">{{ $fiche->numero_agent }}</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="text-muted small">Pont de départ</div>
            <div class="fw-semibold">{{ $fiche->nom_pont ?: '—' }}</div>
            <small class="text-muted">{{ $fiche->nom_parc ?: '' }}</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="text-muted small">Localisation</div>
            <div class="fw-semibold">{{ $localisation }}</div>
            @if($fiche->usine)
              <small class="text-muted">Usine : {{ $fiche->usine }}</small>
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="text-muted small">Chargement</div>
            <div class="fw-semibold">{{ $fiche->date_chargement?->format('d/m/Y') ?? '—' }}</div>
            <small class="text-muted">{{ $etapes->count() }} étape(s)</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Historique des transferts</h5>
      </div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Type</th>
              <th>Agent</th>
              <th>Source</th>
              <th>Destination</th>
              <th class="text-end">Poids</th>
            </tr>
          </thead>
          <tbody>
            @forelse($etapes as $etape)
              <tr>
                <td>{{ $etape->ordre }}</td>
                <td>{{ $etape->date_etape?->format('d/m/Y') }}</td>
                <td><span class="badge bg-label-primary">{{ $etape->libelleType() }}</span></td>
                <td>
                  {{ $etape->nom_agent ?: '—' }}
                  @if($etape->numero_agent)
                    <div class="small text-muted">{{ $etape->numero_agent }}</div>
                  @endif
                </td>
                <td>{{ $etape->nom_parc_source ?: '—' }}</td>
                <td>
                  @if($etape->type_etape === 'cloture_usine')
                    Usine : {{ $etape->usine_destination }}
                  @elseif($etape->type_etape === 'transfert_pont')
                    {{ $etape->nom_pont_destination }}
                    @if($etape->nom_parc_destination)
                      <div class="small text-muted">Parc {{ $etape->nom_parc_destination }}</div>
                    @endif
                  @else
                    Camion (transit)
                  @endif
                </td>
                <td class="text-end">{{ number_format((float) $etape->poids, 0, ',', ' ') }} kg</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Aucune étape</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($enCours)
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header bg-primary">
            <h5 class="mb-0 text-white">Transfert vers un pont</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">
              Un autre agent peut reprendre la marchandise. Le pont destination est celui de l’agent sélectionné.
              Le poids sera <strong>ajouté</strong> au parc de ce pont
              @if($fiche->localisation_type === 'parc')
                , après déduction du parc actuel
              @endif
              .
            </p>
            <div class="alert alert-info py-2">
              Poids du transfert :
              <strong>{{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }} kg</strong>
              — sera ajouté au stock entrée du parc destination.
            </div>
            <form method="POST" action="{{ route('sorties_groupees.transfert_pont', $fiche->id) }}" id="formTransfertPont">
              @csrf
              <input type="hidden" name="id_agent" id="transfert_id_agent" required>
              <input type="hidden" name="id_pont" id="transfert_id_pont" value="">
              <div class="mb-3">
                <label class="form-label">Agent <span class="text-danger">*</span></label>
                <input type="text" id="transfert_agent_search" name="agent_display" class="form-control" placeholder="Rechercher par N° ou nom..." autocomplete="off" required>
                <div id="transfert_agent_dropdown" class="dropdown-menu w-100" style="max-height: 220px; overflow-y: auto; display: none;">
                  @foreach($agents as $agent)
                    <a href="#" class="dropdown-item transfert-agent-option"
                      data-id="{{ $agent['id_agent'] }}"
                      data-display="{{ $agent['nom_complet'] }} ({{ $agent['numero_agent'] }})"
                      data-numero="{{ strtolower($agent['numero_agent'] ?? '') }}"
                      data-nom="{{ strtolower($agent['nom_complet'] ?? '') }}">
                      {{ $agent['nom_complet'] }} ({{ $agent['numero_agent'] }})
                    </a>
                  @endforeach
                </div>
              </div>
              <div class="mb-3" id="transfert_pont_section">
                <label class="form-label">Pont destination <span class="text-danger">*</span></label>
                <input type="text" id="transfert_pont_readonly" class="form-control bg-light d-none" readonly>
                <select id="transfert_pont_select" class="form-select d-none">
                  <option value="">— Sélectionner un pont —</option>
                </select>
                <div id="transfert_pont_empty" class="form-text text-muted">
                  Sélectionnez un agent pour afficher son pont.
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Parc / stock destination <span class="text-danger">*</span></label>
                <select name="stock_id" id="transfert_stock_select" class="form-select" required>
                  <option value="">-- Choisir d'abord un agent / pont --</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" name="date_etape" class="form-control" value="{{ date('Y-m-d') }}" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Commentaire</label>
                <input type="text" name="commentaire" class="form-control" maxlength="500">
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-transfer me-1"></i>Enregistrer le transfert
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-header bg-danger">
            <h5 class="mb-0 text-white">Clôturer vers une usine</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">
              Sélectionnez l’agent, son pont, le parc (produit de départ), le poids ≤ stock disponible, puis l’usine.
              Aucun ajout de stock parc — déduction du parc choisi et clôture de la fiche.
            </p>
            <form method="POST" action="{{ route('sorties_groupees.cloture_usine', $fiche->id) }}" id="formClotureUsine">
              @csrf
              <input type="hidden" name="id_agent" id="cloture_id_agent" required>
              <input type="hidden" name="id_pont" id="cloture_id_pont" value="">
              <div class="mb-3">
                <label class="form-label">Agent <span class="text-danger">*</span></label>
                <input type="text" id="cloture_agent_search" name="agent_display" class="form-control" placeholder="Rechercher par N° ou nom..." autocomplete="off" required>
                <div id="cloture_agent_dropdown" class="dropdown-menu w-100" style="max-height: 220px; overflow-y: auto; display: none;">
                  @foreach($agents as $agent)
                    <a href="#" class="dropdown-item cloture-agent-option"
                      data-id="{{ $agent['id_agent'] }}"
                      data-display="{{ $agent['nom_complet'] }} ({{ $agent['numero_agent'] }})"
                      data-numero="{{ strtolower($agent['numero_agent'] ?? '') }}"
                      data-nom="{{ strtolower($agent['nom_complet'] ?? '') }}">
                      {{ $agent['nom_complet'] }} ({{ $agent['numero_agent'] }})
                    </a>
                  @endforeach
                </div>
              </div>
              <div class="mb-3" id="cloture_pont_section">
                <label class="form-label">Pont <span class="text-danger">*</span></label>
                <input type="text" id="cloture_pont_readonly" class="form-control bg-light d-none" readonly>
                <select id="cloture_pont_select" class="form-select d-none">
                  <option value="">— Sélectionner un pont —</option>
                </select>
                <div id="cloture_pont_empty" class="form-text text-muted">
                  Sélectionnez un agent pour afficher ses ponts.
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Parc / stock source <span class="text-danger">*</span></label>
                <select name="stock_id" id="cloture_stock_select" class="form-select" required>
                  <option value="">-- Choisir d'abord un agent / pont --</option>
                </select>
                <small class="text-muted" id="cloture_parc_hint">Parcs ouverts pour le produit de départ.</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Poids (kg) <span class="text-danger">*</span></label>
                <input type="number" name="poids" id="cloture_poids" class="form-control" min="0.01" step="0.01" required
                       placeholder="Ex: {{ number_format((float) $fiche->poids_pont, 0, '.', '') }}"
                       value="{{ old('poids', (float) $fiche->poids_pont) }}">
                <small class="text-muted" id="cloture_poids_hint">
                  Poids en transit : {{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }} kg — plafonné au stock disponible du parc.
                </small>
                <div class="text-danger small mt-1 d-none" id="cloture_poids_error"></div>
              </div>
              <div class="mb-3">
                <label class="form-label">Usine destination <span class="text-danger">*</span></label>
                <select name="usine" class="form-select" required>
                  <option value="">-- Sélectionner une usine --</option>
                  @foreach($usines as $usine)
                    <option value="{{ $usine['nom'] ?? '' }}">
                      {{ $usine['nom'] ?? '' }}
                      @if(!empty($usine['code'])) ({{ $usine['code'] }}) @endif
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" name="date_etape" class="form-control" value="{{ date('Y-m-d') }}" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Commentaire</label>
                <input type="text" name="commentaire" class="form-control" maxlength="500">
              </div>
              <button type="submit" class="btn btn-danger" onclick="return confirm('Clôturer définitivement cette fiche groupée vers l\'usine ?')">
                <i class="bx bx-check me-1"></i>Clôturer la fiche
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    @endif
  </div>
</div>

@if($enCours)
<script>
document.addEventListener('DOMContentLoaded', function () {
  var produitId = {{ (int) $fiche->produit_id }};
  var poidsTransit = {{ (float) $fiche->poids_pont }};
  var stocksUrl = @json(route('sorties_groupees.stocks_pont_produit'));
  var agentsPontsMap = {!! json_encode($agentsPontsMap ?? []) !!};

  function formatKg(n) {
    return Math.round(n || 0).toLocaleString('fr-FR');
  }

  function createPontUi(prefix, mode) {
    var pontSelect = document.getElementById(prefix + '_pont_select');
    var pontReadonly = document.getElementById(prefix + '_pont_readonly');
    var pontEmpty = document.getElementById(prefix + '_pont_empty');
    var idPontHidden = document.getElementById(prefix + '_id_pont');
    var stockSelect = document.getElementById(prefix + '_stock_select');
    var poidsInput = document.getElementById(prefix + '_poids');
    var poidsHint = document.getElementById(prefix + '_poids_hint');
    var poidsError = document.getElementById(prefix + '_poids_error');
    var parcHint = document.getElementById(prefix + '_parc_hint');
    var agentHidden = document.getElementById(prefix + '_id_agent');

    function resetPont() {
      if (idPontHidden) idPontHidden.value = '';
      if (pontReadonly) {
        pontReadonly.value = '';
        pontReadonly.classList.add('d-none');
      }
      if (pontSelect) {
        pontSelect.innerHTML = '<option value="">— Sélectionner un pont —</option>';
        pontSelect.classList.add('d-none');
        pontSelect.value = '';
      }
      if (pontEmpty) {
        pontEmpty.classList.remove('d-none');
        pontEmpty.textContent = 'Sélectionnez un agent pour afficher ses ponts.';
      }
      if (stockSelect) {
        stockSelect.innerHTML = '<option value="">-- Choisir d\'abord un agent / pont --</option>';
      }
      if (mode === 'cloture') {
        appliquerDispo(null, null);
      }
    }

    function appliquerDispo(dispo, nomParc) {
      if (!poidsInput) return;
      if (dispo !== null && dispo !== undefined && !isNaN(dispo)) {
        poidsInput.max = String(dispo);
        if (poidsHint) {
          poidsHint.textContent = 'Poids en transit : ' + formatKg(poidsTransit) + ' kg — max parc'
            + (nomParc ? ' ' + nomParc : '') + ' : ' + formatKg(dispo) + ' kg.';
        }
      } else {
        poidsInput.removeAttribute('max');
        if (poidsHint) {
          poidsHint.textContent = 'Poids en transit : ' + formatKg(poidsTransit)
            + ' kg — plafonné au stock disponible du parc.';
        }
      }
      validerPoids();
    }

    function validerPoids() {
      if (!poidsInput) return true;
      var poids = parseFloat(poidsInput.value || '');
      var opt = stockSelect && stockSelect.options[stockSelect.selectedIndex];
      var dispo = opt ? parseFloat(opt.dataset.disponible || '') : NaN;
      if (poidsError) {
        poidsError.classList.add('d-none');
        poidsError.textContent = '';
      }
      poidsInput.classList.remove('is-invalid');
      if (!poids || poids <= 0) return false;
      if (!isNaN(dispo) && poids > dispo + 0.0001) {
        if (poidsError) {
          poidsError.textContent = 'Le poids ne peut pas dépasser le disponible (' + formatKg(dispo) + ' kg).';
          poidsError.classList.remove('d-none');
        }
        poidsInput.classList.add('is-invalid');
        return false;
      }
      return true;
    }

    function chargerStocks(idPont) {
      if (!stockSelect) return;
      stockSelect.innerHTML = '<option value="">Chargement…</option>';
      if (!idPont) {
        stockSelect.innerHTML = '<option value="">-- Choisir d\'abord un agent / pont --</option>';
        return;
      }
      var params = new URLSearchParams({ id_pont: idPont, produit_id: String(produitId) });
      fetch(stocksUrl + '?' + params.toString(), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          stockSelect.innerHTML = '<option value="">-- Sélectionner un parc --</option>';
          (data.stocks || []).forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s.stock_id;
            opt.dataset.disponible = s.disponible;
            opt.dataset.nomParc = s.nom_parc || 'Parc';
            if (mode === 'transfert') {
              var entrees = Math.round(s.total_entrees || 0);
              opt.textContent = (s.nom_parc || 'Parc')
                + ' — ' + entrees.toLocaleString('fr-FR') + ' kg'
                + ' → ' + (entrees + Math.round(poidsTransit)).toLocaleString('fr-FR') + ' kg après transfert';
            } else {
              opt.textContent = (s.nom_parc || 'Parc')
                + ' — dispo ' + formatKg(s.disponible || 0) + ' kg';
            }
            stockSelect.appendChild(opt);
          });
          if (!(data.stocks || []).length) {
            stockSelect.innerHTML = '<option value="">Aucun stock ouvert pour ce produit</option>';
            if (parcHint) parcHint.textContent = 'Aucun parc ouvert pour le produit de départ sur ce pont.';
            appliquerDispo(null, null);
          } else {
            if (parcHint) parcHint.textContent = 'Parcs ouverts pour le produit de départ.';
            if (data.stocks.length === 1) {
              stockSelect.value = String(data.stocks[0].stock_id);
              if (mode === 'cloture') {
                appliquerDispo(data.stocks[0].disponible, data.stocks[0].nom_parc);
                if (poidsInput && (!poidsInput.value || parseFloat(poidsInput.value) <= 0)) {
                  poidsInput.value = String(Math.min(poidsTransit, data.stocks[0].disponible || poidsTransit));
                }
              }
            } else if (mode === 'cloture') {
              appliquerDispo(null, null);
            }
          }
        })
        .catch(function () {
          stockSelect.innerHTML = '<option value="">Erreur de chargement</option>';
        });
    }

    function selectPont(pont) {
      if (!pont) return;
      if (idPontHidden) idPontHidden.value = String(pont.id);
      chargerStocks(String(pont.id));
    }

    function updatePourAgent(agentId) {
      resetPont();
      if (!agentId) return;
      var ponts = agentsPontsMap[String(agentId)] || agentsPontsMap[agentId] || [];
      if (pontEmpty) pontEmpty.classList.add('d-none');
      if (ponts.length === 0) {
        if (pontEmpty) {
          pontEmpty.classList.remove('d-none');
          pontEmpty.textContent = 'Aucun pont associé à cet agent.';
        }
        return;
      }
      if (ponts.length === 1) {
        if (pontReadonly) {
          pontReadonly.value = ponts[0].label;
          pontReadonly.classList.remove('d-none');
        }
        selectPont(ponts[0]);
        return;
      }
      if (pontSelect) {
        ponts.forEach(function (pont) {
          var option = document.createElement('option');
          option.value = pont.id;
          option.textContent = pont.label;
          pontSelect.appendChild(option);
        });
        pontSelect.classList.remove('d-none');
      }
    }

    if (pontSelect) {
      pontSelect.addEventListener('change', function () {
        var selectedId = this.value;
        if (!selectedId) {
          if (idPontHidden) idPontHidden.value = '';
          if (stockSelect) stockSelect.innerHTML = '<option value="">-- Choisir d\'abord un agent / pont --</option>';
          if (mode === 'cloture') appliquerDispo(null, null);
          return;
        }
        var agentId = agentHidden ? agentHidden.value : '';
        var ponts = agentsPontsMap[String(agentId)] || agentsPontsMap[agentId] || [];
        var pont = ponts.find(function (item) { return String(item.id) === String(selectedId); });
        selectPont(pont || { id: selectedId, label: '' });
      });
    }

    if (stockSelect && mode === 'cloture') {
      stockSelect.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) {
          appliquerDispo(null, null);
          return;
        }
        appliquerDispo(parseFloat(opt.dataset.disponible || ''), opt.dataset.nomParc || null);
      });
    }

    if (poidsInput) {
      poidsInput.addEventListener('input', validerPoids);
    }

    return {
      updatePourAgent: updatePourAgent,
      idPontHidden: idPontHidden,
      stockSelect: stockSelect,
      validerPoids: validerPoids
    };
  }

  var transfertUi = createPontUi('transfert', 'transfert');
  var clotureUi = createPontUi('cloture', 'cloture');

  function bindAgentSearch(inputId, dropdownId, hiddenId, optionClass, onSelect) {
    var input = document.getElementById(inputId);
    var dropdown = document.getElementById(dropdownId);
    var hidden = document.getElementById(hiddenId);
    if (!input || !dropdown || !hidden) return;

    var options = dropdown.querySelectorAll('.' + optionClass);
    input.addEventListener('input', function () {
      var val = this.value.toLowerCase().trim();
      if (hidden.value) {
        hidden.value = '';
        if (typeof onSelect === 'function') onSelect(null);
      }
      var visible = false;
      options.forEach(function (opt) {
        var match = !val
          || (opt.textContent || '').toLowerCase().includes(val)
          || (opt.dataset.numero || '').includes(val)
          || (opt.dataset.nom || '').includes(val);
        opt.style.display = match ? 'block' : 'none';
        if (match && val) visible = true;
      });
      dropdown.style.display = visible ? 'block' : 'none';
    });
    options.forEach(function (opt) {
      opt.addEventListener('click', function (e) {
        e.preventDefault();
        input.value = this.dataset.display;
        hidden.value = this.dataset.id;
        dropdown.style.display = 'none';
        if (typeof onSelect === 'function') onSelect(this.dataset.id);
      });
    });
    document.addEventListener('click', function (e) {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });
  }

  bindAgentSearch(
    'transfert_agent_search',
    'transfert_agent_dropdown',
    'transfert_id_agent',
    'transfert-agent-option',
    transfertUi.updatePourAgent
  );
  bindAgentSearch(
    'cloture_agent_search',
    'cloture_agent_dropdown',
    'cloture_id_agent',
    'cloture-agent-option',
    clotureUi.updatePourAgent
  );

  var formTransfert = document.getElementById('formTransfertPont');
  if (formTransfert) {
    formTransfert.addEventListener('submit', function (e) {
      var hidden = formTransfert.querySelector('input[name="id_agent"]');
      if (!hidden || !hidden.value) {
        e.preventDefault();
        alert('Sélectionnez un agent dans la liste.');
        return;
      }
      if (!transfertUi.idPontHidden || !transfertUi.idPontHidden.value) {
        e.preventDefault();
        alert('Cet agent n\'a pas de pont associé.');
        return;
      }
      if (!transfertUi.stockSelect || !transfertUi.stockSelect.value) {
        e.preventDefault();
        alert('Sélectionnez un parc / stock destination.');
      }
    });
  }

  var formCloture = document.getElementById('formClotureUsine');
  if (formCloture) {
    formCloture.addEventListener('submit', function (e) {
      var hidden = formCloture.querySelector('input[name="id_agent"]');
      if (!hidden || !hidden.value) {
        e.preventDefault();
        alert('Sélectionnez un agent dans la liste.');
        return;
      }
      if (!clotureUi.idPontHidden || !clotureUi.idPontHidden.value) {
        e.preventDefault();
        alert('Sélectionnez le pont de l\'agent.');
        return;
      }
      if (!clotureUi.stockSelect || !clotureUi.stockSelect.value) {
        e.preventDefault();
        alert('Sélectionnez un parc / stock source.');
        return;
      }
      if (!clotureUi.validerPoids()) {
        e.preventDefault();
        alert('Le poids dépasse le stock disponible du parc.');
      }
    });
  }
});
</script>
@endif
@endsection
