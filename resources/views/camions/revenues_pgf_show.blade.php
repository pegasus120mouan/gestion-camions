@extends('layout.main')
@section('page-styles')
<style>
  .btn-attente-prix {
    color: #dc3545;
    font-weight: 600;
    font-size: 0.8125rem;
    padding: 0.15rem 0.5rem;
    border: 1px solid rgba(220, 53, 69, 0.35);
    background: #fff;
  }
  .btn-attente-prix:hover {
    color: #fff;
    background: #dc3545;
    border-color: #dc3545;
  }
  .btn-prix-saisi {
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
  }
</style>
@endsection
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière — PGF</h4>
        <span class="badge bg-label-primary">PGF</span>
        <span class="badge bg-secondary ms-1">{{ $camionsCount }} camion(s)</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('camions.activites') }}" class="btn btn-outline-info btn-sm">
          <i class="bx bx-list-ul me-1"></i>Activités
        </a>
        <a href="{{ route('camions.revenues') }}" class="btn btn-secondary">
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
    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Filtres</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('camions.revenues.show') }}" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Véhicule</label>
            <select name="vehicule" class="form-select">
              <option value="">Tous les véhicules</option>
              @foreach($vehiculesPgf as $vehicule)
                <option value="{{ $vehicule->matricule_vehicule }}" @selected(($filtres['vehicule'] ?? '') === $vehicule->matricule_vehicule)>
                  {{ $vehicule->matricule_vehicule }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" value="{{ $filtres['date_debut'] ?? '' }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="{{ $filtres['date_fin'] ?? '' }}">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-search me-1"></i>Filtrer
            </button>
            <a href="{{ route('camions.revenues.show') }}" class="btn btn-outline-secondary">
              <i class="bx bx-reset me-1"></i>Réinitialiser
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">Montant dû</h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Revenus camions PGF (tickets)</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
          <div class="card-body">
            <h6 class="card-title" style="color: #0f5132;">Montant payé</h6>
            <h3 class="mb-0" style="color: #0f5132;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Paiements bordereaux</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
          <div class="card-body">
            <h6 class="card-title" style="color: #664d03;">Reste à payer</h6>
            <h3 class="mb-0" style="color: #664d03;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Montant dû − montant payé (bordereaux)</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
        <h5 class="card-title mb-0" style="color: #842029;">
          <i class="bx bx-file me-2"></i>Gestion bordereaux
        </h5>
        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalGenererBordereau">
          <i class="bx bx-plus me-1"></i>Générer un bordereau
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>N° bordereau</th>
              <th>Généré le</th>
              <th>Période</th>
              <th class="text-end">Fiches</th>
              <th class="text-end">Poids</th>
              <th class="text-end">Montant</th>
              <th class="text-end">Montant payé</th>
              <th class="text-end">Reste à payer</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($bordereaux ?? [] as $bordereau)
              @php
                $resteBordereau = (int) round($bordereau->reste_a_payer);
                $montantPayeBordereau = (int) round((float) ($bordereau->montant_paye ?? 0));
              @endphp
              <tr>
                <td>
                  <a href="{{ route('camions.revenues.bordereau.pdf', ['id' => $bordereau->id]) }}" target="_blank" rel="noopener" class="fw-bold text-primary text-decoration-none" title="Ouvrir le PDF">
                    {{ $bordereau->numero }}
                  </a>
                </td>
                <td>{{ $bordereau->date_generation ? $bordereau->date_generation->format('d/m/Y') : '-' }}</td>
                <td>
                  {{ $bordereau->date_debut ? $bordereau->date_debut->format('d/m/Y') : '-' }}
                  →
                  {{ $bordereau->date_fin ? $bordereau->date_fin->format('d/m/Y') : '-' }}
                </td>
                <td class="text-end">{{ count($bordereau->fiches_data ?? []) }}</td>
                <td class="text-end">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }} kg</td>
                <td class="text-end text-danger fw-bold">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-success">{{ number_format($montantPayeBordereau, 0, ',', ' ') }} FCFA</td>
                <td class="text-end">
                  @if($resteBordereau > 0)
                    <span class="text-danger fw-bold">{{ number_format($resteBordereau, 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-muted">0 FCFA</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($resteBordereau > 0)
                    <button type="button"
                      class="btn btn-sm btn-outline-success btn-paiement-bordereau"
                      title="Enregistrer un paiement"
                      data-bs-toggle="modal"
                      data-bs-target="#modalPaiementBordereau"
                      data-bordereau-id="{{ $bordereau->id }}"
                      data-bordereau-numero="{{ $bordereau->numero }}"
                      data-bordereau-reste="{{ $resteBordereau }}">
                      <i class="bx bx-money"></i>
                    </button>
                  @endif
                  <a href="{{ route('camions.revenues.bordereau.pdf', ['id' => $bordereau->id]) }}" target="_blank" class="btn btn-sm btn-outline-info" title="PDF">
                    <i class="bx bx-printer"></i>
                  </a>
                  <form method="POST" action="{{ route('camions.revenues.bordereau.destroy', ['id' => $bordereau->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce bordereau ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">Aucun bordereau généré pour PGF</td>
              </tr>
            @endforelse
          </tbody>
          @if(($bordereaux ?? collect())->count() > 0)
            <tfoot>
              <tr>
                <td colspan="5" class="text-end"><strong>Totaux</strong></td>
                <td class="text-end text-danger fw-bold">{{ number_format($bordereaux->sum('montant_total'), 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-success fw-bold">{{ number_format($bordereaux->sum('montant_paye'), 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-danger fw-bold">{{ number_format($bordereaux->sum(fn ($b) => $b->reste_a_payer), 0, ',', ' ') }} FCFA</td>
                <td></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-ticket me-2"></i>Numéros de tickets usine camion ({{ $tickets->count() }})</h5>
        <a href="{{ route('camions.activites') }}" class="btn btn-sm btn-outline-primary">
          <i class="bx bx-list-ul me-1"></i>Voir activités
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date ticket</th>
              <th>N° Ticket</th>
              <th>Usine</th>
              <th>Agent</th>
              <th>Pont</th>
              <th>Véhicule</th>
              <th class="text-end">Poids Usine</th>
              <th class="text-end">Prix unitaire</th>
              <th class="text-end">Montant</th>
              <th>N° Bordereau</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $ticket)
              <tr>
                <td>{{ $ticket['date_ticket']?->format('d-m-Y') ?? '—' }}</td>
                <td>
                  <a href="{{ route('camions.activites', ['q' => $ticket['numero_ticket']]) }}" class="text-primary fw-semibold">
                    {{ $ticket['numero_ticket'] ?: '—' }}
                  </a>
                </td>
                <td>{{ $ticket['nom_usine'] }}</td>
                <td>{{ $ticket['nom_agent'] }}</td>
                <td>{{ $ticket['nom_pont'] }}</td>
                <td>
                  @if(!empty($ticket['vehicule_id']))
                    <a href="{{ route('vehicules.depenses', ['vehicule_id' => $ticket['vehicule_id'], 'matricule' => $ticket['matricule_vehicule']]) }}" class="text-primary">
                      {{ $ticket['matricule_vehicule'] }}
                    </a>
                  @else
                    {{ $ticket['matricule_vehicule'] }}
                  @endif
                </td>
                <td class="text-end">{{ number_format((float) $ticket['poids'], 0, ',', ' ') }}</td>
                @php
                  $prixAffiche = $ticket['prix_unitaire'] !== null
                    ? rtrim(rtrim(number_format((float) $ticket['prix_unitaire'], 2, '.', ''), '0'), '.')
                    : '';
                  $surBordereau = !empty($ticket['bordereau_pgf_id']);
                @endphp
                <td class="text-end js-prix-cell-pgf"
                    style="min-width: 110px;"
                    data-ticket-id="{{ $ticket['id_ticket'] }}"
                    data-ticket-numero="{{ $ticket['numero_ticket'] }}"
                    data-poids="{{ (float) $ticket['poids'] }}"
                    data-save-url="{{ route('tickets.prix_unitaire', $ticket['id_ticket']) }}"
                    data-prix="{{ $prixAffiche }}">
                  @if($surBordereau)
                    @if($ticket['prix_unitaire'] !== null)
                      <span class="badge bg-label-primary" title="Prix figé (ticket déjà sur bordereau)">{{ $prixAffiche }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  @elseif($ticket['prix_unitaire'] !== null)
                    <button type="button" class="btn btn-sm btn-outline-primary btn-prix-saisi js-open-prix-modal" title="Modifier le prix unitaire">
                      {{ $prixAffiche }}
                    </button>
                  @else
                    <button type="button" class="btn btn-sm btn-attente-prix js-open-prix-modal">
                      En attente
                    </button>
                  @endif
                </td>
                <td class="text-end fw-semibold js-montant-pgf">
                  {{ $ticket['montant'] !== null ? number_format((float) $ticket['montant'], 0, ',', ' ').' FCFA' : '—' }}
                </td>
                <td>
                  @if(!empty($ticket['numero_bordereau']) && !empty($ticket['bordereau_pgf_id']))
                    <a href="{{ route('camions.revenues.bordereau.pdf', ['id' => $ticket['bordereau_pgf_id']]) }}" target="_blank" rel="noopener" class="fw-semibold text-primary text-decoration-none">
                      {{ $ticket['numero_bordereau'] }}
                    </a>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">Aucun ticket usine pour ces filtres.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPaiementBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formPaiementBordereau" method="POST" action="">
        @csrf
        <div class="modal-body">
          <div class="alert alert-secondary mb-2">
            <strong>Bordereau :</strong> <span id="paiementBordereauNumero">—</span>
          </div>
          <div class="alert alert-info">
            <strong>Reste à payer :</strong> <span id="paiementBordereauReste">0</span> FCFA
          </div>
          <div class="alert alert-success mb-2">
            <strong>Caisse locale :</strong> {{ number_format((int) ($soldeCaisseLocale ?? 0), 0, ',', ' ') }} FCFA
            <br><small>Le paiement sera débité de la caisse locale.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="paiementBordereauMontant" class="form-control" required placeholder="Ex: 4 685 000" inputmode="numeric" autocomplete="off" />
            <small class="text-muted">Maximum : reste dû et solde caisse.</small>
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
          <button type="submit" class="btn btn-success"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalGenererBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-file me-2"></i>Générer un bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('camions.revenues.bordereau.store') }}" id="formGenererBordereau">
        @csrf
        <div class="modal-body">
          <p class="text-muted small mb-3">
            Un numéro de bordereau sera attribué automatiquement à la génération
            (ex.&nbsp;: <strong>{{ $exempleNumeroBordereau ?? 'BORD-PG1' }}</strong>).
          </p>
          <div class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
              <label class="form-label">Période début <span class="text-danger">*</span></label>
              <input type="date" name="date_debut" id="bordereau_date_debut" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Période fin <span class="text-danger">*</span></label>
              <input type="date" name="date_fin" id="bordereau_date_fin" class="form-control" required>
            </div>
            <div class="col-md-4">
              <button type="button" class="btn btn-outline-primary w-100" id="btnChargerFichesBordereau">
                <i class="bx bx-search me-1"></i>Charger les tickets
              </button>
            </div>
          </div>

          <div id="bordereauChargement" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Chargement des tickets…</p>
          </div>

          <div id="bordereauAucuneFiche" class="alert alert-warning d-none mb-0">
            Aucun ticket avec montant disponible sur cette période (tickets déjà inclus dans un bordereau exclus).
          </div>

          <div id="bordereauListeFiches" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToutSelectionnerBordereau">Tout sélectionner</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToutDeselectionnerBordereau">Tout désélectionner</button>
              </div>
              <div class="text-end">
                <span class="badge bg-label-info me-1" id="bordereauNbSelection">0 ticket(s)</span>
                <span class="badge bg-label-danger" id="bordereauMontantSelection">0 FCFA</span>
              </div>
            </div>
            <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
              <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="sticky-top">
                  <tr>
                    <th style="width:40px"><input type="checkbox" id="checkAllFichesBordereau" checked></th>
                    <th>N° ticket</th>
                    <th>Date</th>
                    <th>Véhicule</th>
                    <th>Usine</th>
                    <th class="text-end">Poids</th>
                    <th class="text-end">Montant</th>
                  </tr>
                </thead>
                <tbody id="tbodyFichesBordereau"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger" id="btnSubmitBordereau" disabled>
            <i class="bx bx-check me-1"></i>Générer le bordereau
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
  var urlFiches = @json(route('camions.revenues.bordereau.fiches'));
  var tbody = document.getElementById('tbodyFichesBordereau');
  var listeBlock = document.getElementById('bordereauListeFiches');
  var aucuneBlock = document.getElementById('bordereauAucuneFiche');
  var chargementBlock = document.getElementById('bordereauChargement');
  var btnSubmit = document.getElementById('btnSubmitBordereau');
  var nbSel = document.getElementById('bordereauNbSelection');
  var montantSel = document.getElementById('bordereauMontantSelection');
  var checkAll = document.getElementById('checkAllFichesBordereau');
  var soldeCaisseLocale = {{ (int) ($soldeCaisseLocale ?? 0) }};

  function formatNombre(n) {
    return new Intl.NumberFormat('fr-FR').format(n);
  }

  function formatMontantSaisie(value) {
    var digits = String(value || '').replace(/\D/g, '');
    if (!digits) return '';
    return parseInt(digits, 10).toLocaleString('fr-FR').replace(/\u202F/g, ' ').replace(/,/g, ' ');
  }

  function majTotauxSelection() {
    var checks = tbody.querySelectorAll('.fiche-bordereau-check:checked');
    var montant = 0;
    checks.forEach(function(c) {
      montant += parseInt(c.dataset.montant || '0', 10);
    });
    nbSel.textContent = checks.length + ' ticket(s)';
    montantSel.textContent = formatNombre(montant) + ' FCFA';
    btnSubmit.disabled = checks.length === 0;
    if (checkAll) {
      var all = tbody.querySelectorAll('.fiche-bordereau-check');
      checkAll.checked = all.length > 0 && checks.length === all.length;
    }
  }

  function renderFiches(fiches) {
    tbody.innerHTML = '';
    fiches.forEach(function(f) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="checkbox" class="form-check-input fiche-bordereau-check" name="ticket_ids[]" value="' + f.ticket_id + '" data-montant="' + f.montant + '" checked></td>' +
        '<td><small>' + (f.numero_ticket || '—') + '</small></td>' +
        '<td><small>' + (f.date_dechargement ? f.date_dechargement.split('-').reverse().join('/') : '-') + '</small></td>' +
        '<td>' + (f.matricule_vehicule || '-') + '</td>' +
        '<td><small>' + (f.usine || '—') + '</small></td>' +
        '<td class="text-end">' + formatNombre(Math.round(f.poids || 0)) + '</td>' +
        '<td class="text-end text-danger">' + formatNombre(f.montant || 0) + '</td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) {
      c.addEventListener('change', majTotauxSelection);
    });

    listeBlock.classList.remove('d-none');
    majTotauxSelection();
  }

  document.getElementById('btnChargerFichesBordereau').addEventListener('click', function() {
    var debut = document.getElementById('bordereau_date_debut').value;
    var fin = document.getElementById('bordereau_date_fin').value;
    if (!debut || !fin) {
      alert('Indiquez la période début et fin.');
      return;
    }

    listeBlock.classList.add('d-none');
    aucuneBlock.classList.add('d-none');
    chargementBlock.classList.remove('d-none');
    btnSubmit.disabled = true;

    var params = new URLSearchParams({ date_debut: debut, date_fin: fin });
    fetch(urlFiches + '?' + params.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        chargementBlock.classList.add('d-none');
        if (!data.fiches || data.fiches.length === 0) {
          aucuneBlock.classList.remove('d-none');
          return;
        }
        renderFiches(data.fiches);
      })
      .catch(function() {
        chargementBlock.classList.add('d-none');
        alert('Impossible de charger les tickets.');
      });
  });

  if (checkAll) {
    checkAll.addEventListener('change', function() {
      tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) {
        c.checked = checkAll.checked;
      });
      majTotauxSelection();
    });
  }

  document.getElementById('btnToutSelectionnerBordereau').addEventListener('click', function() {
    tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) { c.checked = true; });
    majTotauxSelection();
  });

  document.getElementById('btnToutDeselectionnerBordereau').addEventListener('click', function() {
    tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) { c.checked = false; });
    majTotauxSelection();
  });

  document.getElementById('formGenererBordereau').addEventListener('submit', function(e) {
    if (tbody.querySelectorAll('.fiche-bordereau-check:checked').length === 0) {
      e.preventDefault();
      alert('Sélectionnez au moins un ticket.');
    }
  });

  document.getElementById('modalGenererBordereau').addEventListener('hidden.bs.modal', function() {
    tbody.innerHTML = '';
    listeBlock.classList.add('d-none');
    aucuneBlock.classList.add('d-none');
    chargementBlock.classList.add('d-none');
    btnSubmit.disabled = true;
  });

  var urlPaiementBordereauBase = @json(url('/camions-pgf/revenues/bordereaux'));
  var formPaiementBordereau = document.getElementById('formPaiementBordereau');
  var inputMontantBordereau = document.getElementById('paiementBordereauMontant');
  var resteCourant = 0;

  document.querySelectorAll('.btn-paiement-bordereau').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.getAttribute('data-bordereau-id');
      var numero = btn.getAttribute('data-bordereau-numero');
      resteCourant = parseInt(btn.getAttribute('data-bordereau-reste') || '0', 10);
      document.getElementById('paiementBordereauNumero').textContent = numero;
      document.getElementById('paiementBordereauReste').textContent = formatNombre(resteCourant);
      formPaiementBordereau.action = urlPaiementBordereauBase + '/' + id + '/paiement';
      var plafond = Math.min(resteCourant, Math.max(0, soldeCaisseLocale));
      inputMontantBordereau.value = formatMontantSaisie(String(plafond));
    });
  });

  if (inputMontantBordereau) {
    inputMontantBordereau.addEventListener('input', function() {
      inputMontantBordereau.value = formatMontantSaisie(inputMontantBordereau.value);
    });
  }

  formPaiementBordereau.addEventListener('submit', function() {
    inputMontantBordereau.value = String(inputMontantBordereau.value || '').replace(/\D/g, '');
  });
})();
</script>
@endsection

@section('page-scripts')
{{-- Modal saisie prix unitaire (après bootstrap.js) --}}
<div class="modal fade" id="modalSaisirPrixUnitaire" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title text-white"><i class="bx bx-edit me-2"></i>Saisir le prix unitaire</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <div class="small text-muted">Ticket</div>
          <div class="fw-semibold" id="modalSaisirPrixTicket">—</div>
        </div>
        <div class="mb-3">
          <div class="small text-muted">Poids usine</div>
          <div class="fw-semibold" id="modalSaisirPrixPoids">—</div>
        </div>
        <div class="mb-3">
          <label for="modalSaisirPrixInput" class="form-label">Prix unitaire (FCFA)</label>
          <input type="text" class="form-control form-control-lg text-end" id="modalSaisirPrixInput" inputmode="decimal" autocomplete="off" placeholder="Ex: 90">
          <div class="invalid-feedback">Indiquez un prix unitaire valide.</div>
        </div>
        <div class="rounded-3 border bg-light p-3 d-flex justify-content-between align-items-center">
          <span class="text-muted">Montant calculé</span>
          <strong class="fs-5" id="modalSaisirPrixMontant">—</strong>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-success" id="modalSaisirPrixValider">
          <i class="bx bx-check me-1"></i>Valider
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPrixUnitaireSaisi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title text-white"><i class="bx bx-check-circle me-2"></i>Prix unitaire saisi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <h5 class="mb-2">Prix unitaire enregistré</h5>
        <p class="text-muted mb-1" id="modalPrixUnitaireSaisiTicket">—</p>
        <p class="mb-0 fw-semibold" id="modalPrixUnitaireSaisiDetails">—</p>
      </div>
      <div class="modal-footer border-0 justify-content-center pb-4">
        <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal" id="modalPrixUnitaireSaisiOk">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  if (typeof bootstrap === 'undefined') {
    console.error('Bootstrap non chargé : modal prix unitaire indisponible.');
    return;
  }

  var csrfToken = @json(csrf_token());
  var saisirEl = document.getElementById('modalSaisirPrixUnitaire');
  var successEl = document.getElementById('modalPrixUnitaireSaisi');
  if (!saisirEl) return;

  var saisirModal = bootstrap.Modal.getOrCreateInstance(saisirEl);
  var successModal = successEl ? bootstrap.Modal.getOrCreateInstance(successEl) : null;
  var currentCell = null;
  var saving = false;
  var shouldReload = false;

  var inputEl = document.getElementById('modalSaisirPrixInput');
  var ticketEl = document.getElementById('modalSaisirPrixTicket');
  var poidsEl = document.getElementById('modalSaisirPrixPoids');
  var montantEl = document.getElementById('modalSaisirPrixMontant');
  var validerBtn = document.getElementById('modalSaisirPrixValider');

  function formatMontant(value) {
    return Math.round(Number(value) || 0).toLocaleString('fr-FR') + ' FCFA';
  }

  function formatPrix(value) {
    return (Number(value) || 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
  }

  function parseNombre(value) {
    return parseFloat(String(value || '').replace(/\s/g, '').replace(',', '.')) || 0;
  }

  function refreshMontantPreview() {
    if (!currentCell || !montantEl || !inputEl) return;
    var poids = parseNombre(currentCell.getAttribute('data-poids'));
    var prix = parseNombre(inputEl.value);
    montantEl.textContent = (prix > 0 && poids > 0) ? formatMontant(prix * poids) : '—';
  }

  function openPrixModal(cell) {
    currentCell = cell;
    var ticketId = cell.getAttribute('data-ticket-id');
    var numero = cell.getAttribute('data-ticket-numero') || '';
    var poids = parseNombre(cell.getAttribute('data-poids'));
    var prix = cell.getAttribute('data-prix') || '';

    ticketEl.textContent = numero !== '' ? numero : ('Ticket #' + ticketId);
    poidsEl.textContent = poids > 0 ? (poids.toLocaleString('fr-FR') + ' kg') : '—';
    inputEl.value = prix;
    inputEl.classList.remove('is-invalid');
    refreshMontantPreview();
    saisirModal.show();
    setTimeout(function () {
      inputEl.focus();
      inputEl.select();
    }, 250);
  }

  function updateCellAfterSave(prix, montantAffiche, montant) {
    if (!currentCell) return;
    currentCell.setAttribute('data-prix', String(prix));

    var btn = currentCell.querySelector('.js-open-prix-modal');
    if (btn) {
      btn.className = 'btn btn-sm btn-outline-primary btn-prix-saisi js-open-prix-modal';
      btn.title = 'Modifier le prix unitaire';
      var display = String(prix);
      if (Math.floor(Number(prix)) === Number(prix)) {
        display = String(Math.round(Number(prix)));
      }
      btn.textContent = display;
    }

    var row = currentCell.closest('tr');
    var montantCell = row ? row.querySelector('.js-montant-pgf') : null;
    var montantTxt = montantAffiche || formatMontant(montant);
    if (montantCell) {
      montantCell.textContent = montantTxt;
    }

    document.getElementById('modalPrixUnitaireSaisiTicket').textContent =
      currentCell.getAttribute('data-ticket-numero') || ('Ticket #' + currentCell.getAttribute('data-ticket-id'));
    document.getElementById('modalPrixUnitaireSaisiDetails').textContent =
      'Prix : ' + formatPrix(prix) + ' FCFA  ·  Montant : ' + montantTxt;
  }

  function validerPrix() {
    if (!currentCell || saving) return;
    var url = currentCell.getAttribute('data-save-url');
    var prix = String(inputEl.value || '').trim();
    if (prix === '' || parseNombre(prix) < 0) {
      inputEl.classList.add('is-invalid');
      return;
    }

    inputEl.classList.remove('is-invalid');
    saving = true;
    validerBtn.disabled = true;
    validerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validation...';

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ prix_unitaire: prix }),
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data.success) {
          throw new Error((result.data && result.data.message) || 'Enregistrement impossible');
        }
        updateCellAfterSave(
          result.data.prix_unitaire != null ? result.data.prix_unitaire : parseNombre(prix),
          result.data.montant_affiche,
          result.data.montant
        );
        shouldReload = true;
        saisirModal.hide();
        if (successModal) {
          successModal.show();
        } else {
          window.location.reload();
        }
      })
      .catch(function (error) {
        inputEl.classList.add('is-invalid');
        alert(error.message || 'Erreur lors de l’enregistrement du prix.');
      })
      .finally(function () {
        saving = false;
        validerBtn.disabled = false;
        validerBtn.innerHTML = '<i class="bx bx-check me-1"></i>Valider';
      });
  }

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('.js-open-prix-modal');
    if (!btn) return;
    event.preventDefault();
    var cell = btn.closest('.js-prix-cell-pgf');
    if (cell) openPrixModal(cell);
  });

  if (inputEl) {
    inputEl.addEventListener('input', refreshMontantPreview);
    inputEl.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        validerPrix();
      }
    });
  }

  if (validerBtn) {
    validerBtn.addEventListener('click', validerPrix);
  }

  if (successEl) {
    successEl.addEventListener('hidden.bs.modal', function () {
      if (shouldReload) {
        window.location.reload();
      }
    });
  }
})();
</script>
@endsection
