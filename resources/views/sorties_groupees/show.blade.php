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
              Chaîne de transferts : le parc destination reçoit
              <strong>son stock + le stock actuel du parc source</strong>
              (stock déjà présent + transferts reçus), pas seulement le poids initial de chargement.
            </p>
            @if($stockLocalisation)
              <div class="alert alert-warning py-2 small mb-2">
                Parc source actuel :
                <strong>{{ $stockLocalisation->nom_pont }} / {{ $stockLocalisation->nom_parc }}</strong>
                — stock actuel chaîne
                <strong>{{ number_format((float) ($stockActuelChaine ?? 0), 0, ',', ' ') }} kg</strong>
                (entrées {{ number_format((float) $stockLocalisation->total_entrees, 0, ',', ' ') }} kg).
              </div>
              <div class="alert alert-info py-2">
                Poids qui sera crédité à destination :
                <strong>{{ number_format((float) ($stockActuelChaine ?? 0), 0, ',', ' ') }} kg</strong>
              </div>
            @else
              <div class="alert alert-info py-2">
                Marchandise en transit camion — poids crédité :
                <strong>{{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }} kg</strong>
              </div>
            @endif
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
            @php
              $clotureAgentDisplay = trim(($fiche->nom_agent ?? '') . ($fiche->numero_agent ? ' (' . $fiche->numero_agent . ')' : ''));
              $clotureSurParc = $stockLocalisation !== null;
              $cloturePontLabel = $clotureSurParc
                ? trim(($stockLocalisation->nom_pont ?? '') . ($stockLocalisation->code_pont ? ' (' . $stockLocalisation->code_pont . ')' : ''))
                : '';
              $clotureParcLabel = $clotureSurParc
                ? (($stockLocalisation->nom_parc ?? 'Parc') . ' — stock actuel ' . number_format((float) ($stockActuelChaine ?? 0), 0, ',', ' ') . ' kg')
                : '';
              $clotureMaxPoids = (float) ($stockActuelChaine ?? 0);
            @endphp
            <p class="text-muted small">
              @if($clotureSurParc)
                Clôture sur le <strong>même agent</strong>, le <strong>même pont</strong> et le <strong>même parc</strong> où se trouve la marchandise.
                Saisissez uniquement le poids et l’usine.
              @else
                La marchandise est encore en transit : transférez-la d’abord vers un parc avant de clôturer.
              @endif
            </p>
            <form method="POST" action="{{ route('sorties_groupees.cloture_usine', $fiche->id) }}" id="formClotureUsine">
              @csrf
              <input type="hidden" name="id_agent" id="cloture_id_agent" value="{{ old('id_agent', $fiche->id_agent) }}" required>
              <input type="hidden" name="agent_display" id="cloture_agent_display" value="{{ old('agent_display', $clotureAgentDisplay) }}">
              <input type="hidden" name="id_pont" id="cloture_id_pont" value="{{ $clotureSurParc ? $stockLocalisation->id_pont : '' }}">
              <input type="hidden" name="stock_id" id="cloture_stock_id" value="{{ $clotureSurParc ? $stockLocalisation->id : '' }}" @if($clotureSurParc) required @endif>

              <div class="mb-3">
                <label class="form-label">Agent</label>
                <input type="text" class="form-control bg-light" value="{{ $clotureAgentDisplay ?: '—' }}" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Pont</label>
                <input type="text" class="form-control bg-light" value="{{ $cloturePontLabel ?: '—' }}" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Parc / stock source</label>
                <input type="text" class="form-control bg-light" value="{{ $clotureParcLabel ?: '—' }}" readonly>
                @if($clotureSurParc)
                  <small class="text-muted" id="cloture_poids_hint_base">Figé sur la localisation actuelle de la fiche.</small>
                @endif
              </div>
              <div class="mb-3">
                <label class="form-label">Poids (kg) <span class="text-danger">*</span></label>
                <input type="number" name="poids" id="cloture_poids" class="form-control" min="0.01" step="0.01" required
                       placeholder="Saisir le poids"
                       value="{{ old('poids') }}"
                       @if($clotureSurParc && $clotureMaxPoids > 0) max="{{ $clotureMaxPoids }}" @endif
                       @if(!$clotureSurParc) disabled @endif>
                <small class="text-muted" id="cloture_poids_hint">
                  @if($clotureSurParc)
                    À renseigner manuellement — max {{ number_format($clotureMaxPoids, 0, ',', ' ') }} kg.
                  @else
                    Transfert vers un parc requis avant clôture.
                  @endif
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
              <button type="submit" class="btn btn-danger" id="btnClotureUsine" @if(!$clotureSurParc) disabled @endif>
                <i class="bx bx-check me-1"></i>Clôturer la fiche
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal confirmation clôture usine -->
    <div class="modal fade" id="modalConfirmClotureUsine" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title text-white">
              <i class="bx bx-error-circle me-2"></i>Confirmer la clôture
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body text-center py-4">
            <div class="mb-3">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:72px;height:72px;">
                <i class="bx bx-building-house text-danger" style="font-size:2.25rem;"></i>
              </span>
            </div>
            <h5 class="mb-2">Clôturer cette fiche groupée ?</h5>
            <p class="text-muted mb-3">
              Fiche <strong>{{ $fiche->numero_fiche }}</strong>
              · {{ $fiche->matricule_vehicule }}
              · {{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }} kg
            </p>
            <div class="bg-light rounded p-3 text-start small mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Usine</span>
                <strong id="confirmClotureUsineLabel">—</strong>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Agent</span>
                <strong id="confirmClotureAgentLabel">—</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Poids</span>
                <strong id="confirmCloturePoidsLabel">—</strong>
              </div>
            </div>
            <div class="alert alert-warning mb-0 py-2 text-start">
              <i class="bx bx-info-circle me-1"></i>
              La marchandise sera déduite du parc choisi. La fiche passera en non déchargées vers l’usine.
            </div>
          </div>
          <div class="modal-footer justify-content-center border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="bx bx-x me-1"></i>Annuler
            </button>
            <button type="button" class="btn btn-danger" id="btnConfirmClotureUsine">
              <i class="bx bx-check me-1"></i>Oui, clôturer
            </button>
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
  var poidsTransit = {{ (float) ($stockActuelChaine ?? $fiche->poids_pont) }};
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
          poidsHint.textContent = 'À renseigner manuellement — max'
            + (nomParc ? ' ' + nomParc : '') + ' : ' + formatKg(dispo) + ' kg.';
        }
      } else {
        poidsInput.removeAttribute('max');
        if (poidsHint) {
          poidsHint.textContent = 'À renseigner manuellement — max = stock disponible du parc choisi.';
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
              var ajout = Math.round(poidsTransit);
              opt.textContent = (s.nom_parc || 'Parc')
                + ' — stock ' + entrees.toLocaleString('fr-FR') + ' kg'
                + ' → ' + (entrees + ajout).toLocaleString('fr-FR') + ' kg (+' + ajout.toLocaleString('fr-FR') + ' kg fiche)';
            } else {
              var chaine = (s.stock_actuel_chaine !== undefined && s.stock_actuel_chaine !== null)
                ? s.stock_actuel_chaine
                : s.disponible;
              opt.dataset.disponible = chaine;
              opt.textContent = (s.nom_parc || 'Parc')
                + ' — stock actuel ' + formatKg(chaine || 0) + ' kg';
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
                var chaineOne = (data.stocks[0].stock_actuel_chaine !== undefined && data.stocks[0].stock_actuel_chaine !== null)
                  ? data.stocks[0].stock_actuel_chaine
                  : data.stocks[0].disponible;
                appliquerDispo(chaineOne, data.stocks[0].nom_parc);
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
  var clotureMaxPoids = {{ (float) ($stockActuelChaine ?? 0) }};
  var clotureSurParc = {{ $stockLocalisation ? 'true' : 'false' }};

  function validerPoidsCloture() {
    var poidsInput = document.getElementById('cloture_poids');
    var poidsError = document.getElementById('cloture_poids_error');
    if (!poidsInput) return false;
    var poids = parseFloat(poidsInput.value || '');
    if (poidsError) {
      poidsError.classList.add('d-none');
      poidsError.textContent = '';
    }
    poidsInput.classList.remove('is-invalid');
    if (!poids || poids <= 0) return false;
    if (clotureMaxPoids > 0 && poids > clotureMaxPoids + 0.0001) {
      if (poidsError) {
        poidsError.textContent = 'Le poids ne peut pas dépasser le stock actuel (' + formatKg(clotureMaxPoids) + ' kg).';
        poidsError.classList.remove('d-none');
      }
      poidsInput.classList.add('is-invalid');
      return false;
    }
    return true;
  }

  var cloturePoidsInput = document.getElementById('cloture_poids');
  if (cloturePoidsInput) {
    cloturePoidsInput.addEventListener('input', validerPoidsCloture);
  }

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
  var modalClotureEl = document.getElementById('modalConfirmClotureUsine');
  var btnConfirmCloture = document.getElementById('btnConfirmClotureUsine');
  var clotureConfirmed = false;

  function formatKgConfirm(n) {
    return Math.round(n || 0).toLocaleString('fr-FR') + ' kg';
  }

  if (formCloture && modalClotureEl) {
    formCloture.addEventListener('submit', function (e) {
      if (clotureConfirmed) return;

      e.preventDefault();
      if (!clotureSurParc) {
        alert('Transférez d\'abord la marchandise vers un parc avant de clôturer.');
        return;
      }
      var stockId = formCloture.querySelector('input[name="stock_id"]');
      var agentId = formCloture.querySelector('input[name="id_agent"]');
      if (!agentId || !agentId.value || !stockId || !stockId.value) {
        alert('Localisation agent / parc manquante sur la fiche.');
        return;
      }
      var poidsInputCheck = document.getElementById('cloture_poids');
      var poidsVal = poidsInputCheck ? parseFloat(poidsInputCheck.value || '') : NaN;
      if (!poidsVal || poidsVal <= 0) {
        alert('Saisissez le poids à clôturer.');
        if (poidsInputCheck) poidsInputCheck.focus();
        return;
      }
      if (!validerPoidsCloture()) {
        alert('Le poids dépasse le stock actuel du parc.');
        return;
      }

      var usineSelect = formCloture.querySelector('select[name="usine"]');
      var agentDisplay = document.getElementById('cloture_agent_display');
      var poidsInput = document.getElementById('cloture_poids');
      var usineLabel = document.getElementById('confirmClotureUsineLabel');
      var agentLabel = document.getElementById('confirmClotureAgentLabel');
      var poidsLabel = document.getElementById('confirmCloturePoidsLabel');

      if (usineLabel) usineLabel.textContent = (usineSelect && usineSelect.value) ? usineSelect.value : '—';
      if (agentLabel) agentLabel.textContent = (agentDisplay && agentDisplay.value) ? agentDisplay.value : '—';
      if (poidsLabel) poidsLabel.textContent = formatKgConfirm(parseFloat((poidsInput && poidsInput.value) || 0));

      var modal = bootstrap.Modal.getOrCreateInstance(modalClotureEl);
      modal.show();
    });

    if (btnConfirmCloture) {
      btnConfirmCloture.addEventListener('click', function () {
        clotureConfirmed = true;
        var modal = bootstrap.Modal.getOrCreateInstance(modalClotureEl);
        modal.hide();
        btnConfirmCloture.disabled = true;
        btnConfirmCloture.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Clôture…';
        formCloture.submit();
      });
    }
  }
});
</script>
@endif
@endsection
