@extends('layout.main')

@section('content')
<style>
  .tf-fin-show .tf-table { font-size: 0.78rem; }
  .tf-fin-show .tf-table thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    padding: 0.45rem 0.5rem;
    background: #f8f8fa;
    white-space: nowrap;
  }
  .tf-fin-show .tf-table td {
    padding: 0.4rem 0.5rem;
    vertical-align: middle;
    white-space: nowrap;
  }
  .tf-fin-show .btn-sm { font-size: 0.7rem; padding: 0.2rem 0.45rem; }
  .tf-kpi {
    border-radius: 0.6rem;
    padding: 1rem 1.1rem;
    height: 100%;
  }
</style>

<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y tf-fin-show">
    <div class="mb-4">
      <a
        href="{{ route('transferts.financier.index', ['tab' => $type === 'usine' ? 'usines' : 'particuliers']) }}"
        class="text-primary mb-2 d-inline-block"
      >
        <i class="bx bx-arrow-back me-1"></i>Retour à la gestion financière
      </a>
      <h4 class="mb-1">
        @if($type === 'usine')
          <i class="bx bx-buildings text-primary me-2"></i>{{ $clientName }}
        @else
          <i class="bx bx-user text-primary me-2"></i>{{ $clientName }}
        @endif
      </h4>
      <p class="text-muted mb-0">
        @if($code)
          Code : <code class="text-primary">{{ $code }}</code> —
        @endif
        Gestion financière des transferts
      </p>
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

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="tf-kpi" style="background:#fce4ec;">
          <small class="text-muted d-block">Montant dû</small>
          <h5 class="mb-1 text-danger">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h5>
          <small class="text-muted">Total des bordereaux</small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="tf-kpi" style="background:#e8f5e9;">
          <small class="text-muted d-block">Montant payé</small>
          <h5 class="mb-1 text-success">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</h5>
          <small class="text-muted">Paiements sur bordereaux</small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="tf-kpi" style="background:#fff8e1;">
          <small class="text-muted d-block">Reste à payer</small>
          @if($resteAPayer > 0)
            <h5 class="mb-1 text-warning">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h5>
          @else
            <h5 class="mb-1 text-success"><i class="bx bx-check-circle"></i> Soldé</h5>
          @endif
          <small class="text-muted">Total bordereaux − montant payé</small>
        </div>
      </div>
    </div>

    {{-- Bordereaux --}}
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#f8d7da;border-bottom:1px solid #f5c2c7;">
        <h5 class="card-title mb-0" style="color:#842029;">
          <i class="bx bx-file me-2"></i>Gestion bordereaux
        </h5>
        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalGenererBordereau">
          <i class="bx bx-plus me-1"></i>Générer un bordereau
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover tf-table mb-0">
          <thead>
            <tr>
              <th>N° bordereau</th>
              <th>Généré le</th>
              <th>Période</th>
              <th class="text-end">Transferts</th>
              <th class="text-end">Poids</th>
              <th class="text-end">Montant</th>
              <th class="text-end">Montant payé</th>
              <th class="text-end">Reste à payer</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($bordereaux as $bordereau)
              @php $resteBordereau = (float) $bordereau->reste_a_payer; @endphp
              <tr>
                <td>
                  <a href="{{ route('transferts.financier.bordereau.pdf', ['type' => $type, 'id' => $clientId, 'bordereau' => $bordereau]) }}" target="_blank" rel="noopener" class="fw-bold text-primary text-decoration-none" title="Ouvrir le PDF">
                    {{ $bordereau->numero }}
                  </a>
                </td>
                <td>{{ $bordereau->date_generation?->format('d/m/Y') }}</td>
                <td>
                  {{ $bordereau->date_debut?->format('d/m/Y') }}
                  →
                  {{ $bordereau->date_fin?->format('d/m/Y') }}
                </td>
                <td class="text-end">{{ count($bordereau->transferts_data ?? []) }}</td>
                <td class="text-end">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }} kg</td>
                <td class="text-end text-danger fw-bold">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-success">{{ number_format((float) $bordereau->montant_paye, 0, ',', ' ') }} FCFA</td>
                <td class="text-end">
                  @if($resteBordereau > 0)
                    <span class="text-danger fw-bold">{{ number_format($resteBordereau, 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-success">Soldé</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($resteBordereau > 0)
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-success"
                      data-bs-toggle="modal"
                      data-bs-target="#modalPaiementBordereau"
                      data-action="{{ route('transferts.financier.bordereau.paiement', ['type' => $type, 'id' => $clientId, 'bordereau' => $bordereau]) }}"
                      data-numero="{{ $bordereau->numero }}"
                      data-reste="{{ (int) round($resteBordereau) }}"
                      title="Payer"
                    >
                      <i class="bx bx-money"></i>
                    </button>
                  @endif
                  <a href="{{ route('transferts.financier.bordereau.pdf', ['type' => $type, 'id' => $clientId, 'bordereau' => $bordereau]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info" title="PDF">
                    <i class="bx bx-file"></i>
                  </a>
                  <a href="{{ route('transferts.financier.bordereau.show', ['type' => $type, 'id' => $clientId, 'bordereau' => $bordereau]) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                    <i class="bx bx-show"></i>
                  </a>
                  @if((float) $bordereau->montant_paye <= 0)
                    <form method="POST" action="{{ route('transferts.financier.bordereau.destroy', ['type' => $type, 'id' => $clientId, 'bordereau' => $bordereau]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce bordereau ?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">Aucun bordereau généré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Détail transferts --}}
    <div class="card mb-4">
      <div class="card-header" style="background-color:#f8d7da;border-bottom:1px solid #f5c2c7;">
        <h5 class="card-title mb-0" style="color:#842029;">
          <i class="bx bx-transfer-alt me-2"></i>Détail des transferts ({{ $transferts->count() }})
        </h5>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover tf-table mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Véhicule</th>
              <th>Départ</th>
              <th>Destination</th>
              <th class="text-end">Poids</th>
              <th class="text-end">PU</th>
              <th class="text-end">Montant</th>
              <th>Statut</th>
              <th>Paiement</th>
              <th>Bordereau</th>
            </tr>
          </thead>
          <tbody>
            @forelse($transferts as $transfert)
              <tr>
                <td>{{ $transfert->date_chargement?->format('d/m/Y') }}</td>
                <td><strong>{{ $transfert->matricule_vehicule }}</strong></td>
                <td>{{ $transfert->lieu_depart }}</td>
                <td>{{ $transfert->lieu_destination }}</td>
                <td class="text-end">
                  {{ ($transfert->poids_arrivee ?? $transfert->poids_depart) !== null ? number_format((float) ($transfert->poids_arrivee ?? $transfert->poids_depart), 0, ',', ' ') : '—' }}
                </td>
                <td class="text-end">
                  {{ $transfert->prix_unitaire !== null ? number_format((float) $transfert->prix_unitaire, 0, ',', ' ') : '—' }}
                </td>
                <td class="text-end text-danger fw-semibold">
                  @if($transfert->montant !== null)
                    {{ number_format((float) $transfert->montant, 0, ',', ' ') }} FCFA
                  @else
                    —
                  @endif
                </td>
                <td>
                  @if(($transfert->statut ?? '') === 'decharge')
                    <span class="badge bg-label-secondary">Déchargé</span>
                  @else
                    <span class="badge bg-label-warning">Non déchargé</span>
                  @endif
                </td>
                <td>
                  @if(($transfert->paiement ?? '') === 'paye')
                    <span class="badge bg-label-secondary">Payé</span>
                  @else
                    <span class="badge bg-label-danger">Non payé</span>
                  @endif
                </td>
                <td>
                  @if($transfert->bordereau_transfert_id)
                    <span class="badge bg-label-primary">#{{ $transfert->bordereau_transfert_id }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">Aucun transfert pour ce client</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Historique des paiements --}}
    <div class="card mb-4">
      <div class="card-header" style="background-color:#d1e7dd;border-bottom:1px solid #badbcc;">
        <h5 class="card-title mb-0" style="color:#0f5132;">
          <i class="bx bx-history me-2"></i>Historique des paiements ({{ $historiquePaiements->count() }})
        </h5>
        <small class="text-muted">Paiements enregistrés sur les bordereaux de ce client</small>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover tf-table mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Bordereau</th>
              <th class="text-end">Montant</th>
              <th>Observation</th>
            </tr>
          </thead>
          <tbody>
            @forelse($historiquePaiements as $paiement)
              <tr>
                <td>{{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                <td>
                  @if($paiement->bordereau)
                    <a href="{{ route('transferts.financier.bordereau.pdf', ['type' => $type, 'id' => $clientId, 'bordereau' => $paiement->bordereau]) }}" target="_blank" rel="noopener" class="badge bg-label-primary text-decoration-none">
                      {{ $paiement->bordereau->numero }}
                    </a>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end fw-semibold text-success">{{ number_format((float) $paiement->montant, 0, ',', ' ') }} FCFA</td>
                <td>{{ $paiement->observation ?: '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucun paiement enregistré</td>
              </tr>
            @endforelse
          </tbody>
          @if($historiquePaiements->isNotEmpty())
            <tfoot>
              <tr class="fw-bold">
                <td colspan="2">Total payé</td>
                <td class="text-end text-success">{{ number_format((float) $historiquePaiements->sum('montant'), 0, ',', ' ') }} FCFA</td>
                <td></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Modal générer bordereau --}}
<div class="modal fade" id="modalGenererBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-file me-2"></i>Générer un bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('transferts.financier.bordereau.store', ['type' => $type, 'id' => $clientId]) }}" id="formGenererBordereau">
        @csrf
        <div class="modal-body">
          <p class="text-muted small mb-3">
            Numéro automatique
            @if(!empty($exempleNumeroBordereau))
              (ex. : <strong>{{ $exempleNumeroBordereau }}</strong>).
            @endif
            Seuls les transferts <strong>déchargés</strong>, avec montant, non déjà sur un bordereau, sont éligibles.
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
              <button type="button" class="btn btn-outline-primary w-100" id="btnChargerTransfertsBordereau">
                <i class="bx bx-search me-1"></i>Charger les transferts
              </button>
            </div>
          </div>

          <div id="bordereauChargement" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Chargement…</p>
          </div>
          <div id="bordereauAucun" class="alert alert-warning d-none mb-0">
            Aucun transfert déchargé éligible sur cette période.
          </div>
          <div id="bordereauListe" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToutSelectionner">Tout sélectionner</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToutDeselectionner">Tout désélectionner</button>
              </div>
              <div>
                <span class="badge bg-label-info me-1" id="bordereauNbSelection">0</span>
                <span class="badge bg-label-danger" id="bordereauMontantSelection">0 FCFA</span>
              </div>
            </div>
            <div class="table-responsive" style="max-height:360px;overflow-y:auto;">
              <table class="table table-sm table-bordered mb-0">
                <thead class="sticky-top table-light">
                  <tr>
                    <th style="width:40px"><input type="checkbox" id="checkAllTransferts" checked></th>
                    <th>Date</th>
                    <th>Véhicule</th>
                    <th>Trajet</th>
                    <th class="text-end">Poids</th>
                    <th class="text-end">Montant</th>
                  </tr>
                </thead>
                <tbody id="tbodyTransfertsBordereau"></tbody>
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

{{-- Modal paiement bordereau --}}
<div class="modal fade" id="modalPaiementBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formPaiementBordereau" action="#">
        @csrf
        <div class="modal-body">
          <p class="mb-2">Bordereau : <strong id="paiementBordereauNumero">—</strong></p>
          <p class="mb-3">Reste à payer : <strong class="text-danger" id="paiementBordereauReste">—</strong></p>
          <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
          <input type="text" name="montant" id="paiementBordereauMontant" class="form-control" required placeholder="Ex: 1 000 000" inputmode="numeric" autocomplete="off">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  var urlEligibles = @json(route('transferts.financier.bordereau.eligibles', ['type' => $type, 'id' => $clientId]));
  var tbody = document.getElementById('tbodyTransfertsBordereau');
  var listeBlock = document.getElementById('bordereauListe');
  var aucunBlock = document.getElementById('bordereauAucun');
  var chargementBlock = document.getElementById('bordereauChargement');
  var btnSubmit = document.getElementById('btnSubmitBordereau');
  var nbSel = document.getElementById('bordereauNbSelection');
  var montantSel = document.getElementById('bordereauMontantSelection');
  var checkAll = document.getElementById('checkAllTransferts');
  var inputMontantPaiement = document.getElementById('paiementBordereauMontant');
  var formPaiement = document.getElementById('formPaiementBordereau');

  function formatMoney(n) {
    return Number(n || 0).toLocaleString('fr-FR') + ' FCFA';
  }

  function formatMontantSaisie(value) {
    var digits = String(value || '').replace(/\D/g, '');
    if (!digits) return '';
    return parseInt(digits, 10).toLocaleString('fr-FR').replace(/\u202F/g, ' ').replace(/,/g, ' ');
  }

  function updateSelection() {
    var checks = tbody.querySelectorAll('.check-transfert:checked');
    var total = 0;
    checks.forEach(function (c) { total += Number(c.getAttribute('data-montant') || 0); });
    nbSel.textContent = checks.length + ' transfert(s)';
    montantSel.textContent = formatMoney(total);
    btnSubmit.disabled = checks.length === 0;
  }

  document.getElementById('btnChargerTransfertsBordereau').addEventListener('click', function () {
    var debut = document.getElementById('bordereau_date_debut').value;
    var fin = document.getElementById('bordereau_date_fin').value;
    if (!debut || !fin) {
      alert('Indiquez la période.');
      return;
    }

    listeBlock.classList.add('d-none');
    aucunBlock.classList.add('d-none');
    chargementBlock.classList.remove('d-none');
    btnSubmit.disabled = true;
    tbody.innerHTML = '';

    fetch(urlEligibles + '?date_debut=' + encodeURIComponent(debut) + '&date_fin=' + encodeURIComponent(fin), {
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        chargementBlock.classList.add('d-none');
        var rows = data.transferts || [];
        if (!rows.length) {
          aucunBlock.classList.remove('d-none');
          return;
        }
        rows.forEach(function (t) {
          var tr = document.createElement('tr');
          tr.innerHTML =
            '<td><input type="checkbox" class="form-check-input check-transfert" name="transfert_ids[]" value="' + t.id + '" data-montant="' + t.montant + '" checked></td>' +
            '<td>' + (t.date_chargement || '—') + '</td>' +
            '<td><strong>' + (t.matricule_vehicule || '—') + '</strong></td>' +
            '<td>' + (t.lieu_depart || '—') + ' → ' + (t.lieu_destination || '—') + '</td>' +
            '<td class="text-end">' + Number(t.poids || 0).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-end text-danger fw-semibold">' + formatMoney(t.montant) + '</td>';
          tbody.appendChild(tr);
        });
        listeBlock.classList.remove('d-none');
        checkAll.checked = true;
        updateSelection();
      })
      .catch(function () {
        chargementBlock.classList.add('d-none');
        alert('Erreur de chargement des transferts.');
      });
  });

  tbody.addEventListener('change', function (e) {
    if (e.target.classList.contains('check-transfert')) updateSelection();
  });
  checkAll.addEventListener('change', function () {
    tbody.querySelectorAll('.check-transfert').forEach(function (c) { c.checked = checkAll.checked; });
    updateSelection();
  });
  document.getElementById('btnToutSelectionner').addEventListener('click', function () {
    checkAll.checked = true;
    checkAll.dispatchEvent(new Event('change'));
  });
  document.getElementById('btnToutDeselectionner').addEventListener('click', function () {
    checkAll.checked = false;
    checkAll.dispatchEvent(new Event('change'));
  });

  document.getElementById('modalPaiementBordereau').addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    if (!btn) return;
    formPaiement.action = btn.getAttribute('data-action') || '#';
    document.getElementById('paiementBordereauNumero').textContent = btn.getAttribute('data-numero') || '—';
    var reste = Number(btn.getAttribute('data-reste') || 0);
    document.getElementById('paiementBordereauReste').textContent = formatMoney(reste);
    inputMontantPaiement.setAttribute('data-plafond', String(reste > 0 ? reste : 0));
    inputMontantPaiement.value = reste > 0 ? formatMontantSaisie(String(reste)) : '';
  });

  if (inputMontantPaiement) {
    inputMontantPaiement.addEventListener('input', function () {
      this.value = formatMontantSaisie(this.value);
      var plafond = parseInt(this.getAttribute('data-plafond') || '0', 10);
      var montant = parseInt(String(this.value || '').replace(/\D/g, '') || '0', 10);
      if (plafond > 0 && montant > plafond) {
        this.value = formatMontantSaisie(String(plafond));
      }
    });
  }

  if (formPaiement) {
    formPaiement.addEventListener('submit', function () {
      inputMontantPaiement.value = String(inputMontantPaiement.value || '').replace(/\D/g, '');
    });
  }
})();
</script>
@endsection
