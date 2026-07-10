@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h4 class="mb-1">Effectuer un paiement</h4>
        <p class="text-muted mb-0">Liste des bordereaux agents — enregistrez un paiement directement ici.</p>
      </div>
      <div class="d-flex gap-3 text-end">
        <div>
          <div class="text-muted small">À payer</div>
          <div class="fw-bold">{{ number_format($stats['a_payer'], 0, ',', ' ') }}</div>
        </div>
        <div>
          <div class="text-muted small">Reste total</div>
          <div class="fw-bold text-danger">{{ number_format($stats['reste_total'], 0, ',', ' ') }} FCFA</div>
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error') || $errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') ?: $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('effectuer_paiement.index') }}" class="row g-2 align-items-end">
          <div class="col-md-5 col-lg-4">
            <label class="form-label small text-uppercase text-muted">Recherche</label>
            <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}"
              placeholder="N° bordereau, agent…">
          </div>
          <div class="col-md-4 col-lg-3">
            <label class="form-label small text-uppercase text-muted">Statut</label>
            <select name="statut" class="form-select form-select-sm">
              <option value="a_payer" @selected($filters['statut'] === 'a_payer')>À payer</option>
              <option value="soldes" @selected($filters['statut'] === 'soldes')>Soldés</option>
              <option value="tous" @selected($filters['statut'] === 'tous')>Tous</option>
            </select>
          </div>
          <div class="col-md-3 col-lg-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bx bx-search me-1"></i>Filtrer
            </button>
            <a href="{{ route('effectuer_paiement.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bx bx-file me-1"></i>Bordereaux</h6>
        <span class="badge bg-label-secondary">{{ $bordereaux->total() }} bordereau(x)</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>N° bordereau</th>
                <th>Agent</th>
                <th>Date</th>
                <th class="text-end">Total</th>
                <th class="text-end">Payé</th>
                <th class="text-end">Reste</th>
                <th class="text-end">Financement</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($bordereaux as $bordereau)
                @php
                  $reste = (int) round($bordereau->reste_a_payer);
                  $idAgent = (int) $bordereau->id_agent;
                  $financement = (int) ($financements[$idAgent] ?? 0);
                  $plafond = $financement > 0
                    ? ($reste > 0 ? min($reste, $financement) : $financement)
                    : ($reste > 0 ? $reste : 0);
                @endphp
                <tr>
                  <td>
                    <a href="{{ route('gestionfinanciere.agent.bordereau.pdf', ['id_agent' => $idAgent, 'id' => $bordereau->id]) }}"
                      target="_blank" class="fw-semibold text-primary text-decoration-none">
                      {{ $bordereau->numero }}
                    </a>
                  </td>
                  <td>
                    <a href="{{ route('gestionfinanciere.agent.show', ['id_agent' => $idAgent]) }}">
                      {{ $bordereau->agent_nom ?: ('Agent #'.$idAgent) }}
                    </a>
                    @if($bordereau->agent_numero)
                      <div class="small text-muted">{{ $bordereau->agent_numero }}</div>
                    @endif
                  </td>
                  <td>{{ $bordereau->date_generation?->format('d/m/Y') ?? '—' }}</td>
                  <td class="text-end">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }}</td>
                  <td class="text-end text-success">{{ number_format((float) ($bordereau->montant_paye ?? 0), 0, ',', ' ') }}</td>
                  <td class="text-end fw-semibold {{ $reste > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($reste, 0, ',', ' ') }}
                  </td>
                  <td class="text-end">
                    @if($financement > 0)
                      <span class="badge bg-label-warning">{{ number_format($financement, 0, ',', ' ') }}</span>
                    @else
                      <span class="text-muted">0</span>
                    @endif
                  </td>
                  <td class="text-end">
                    @if($reste > 0)
                      <button type="button"
                        class="btn btn-sm btn-success btn-paiement-bordereau"
                        data-bs-toggle="modal"
                        data-bs-target="#modalPaiementBordereau"
                        data-agent-id="{{ $idAgent }}"
                        data-bordereau-id="{{ $bordereau->id }}"
                        data-bordereau-numero="{{ $bordereau->numero }}"
                        data-bordereau-reste="{{ $reste }}"
                        data-financement="{{ $financement }}"
                        data-plafond="{{ $plafond }}">
                        <i class="bx bx-money me-1"></i>Payer
                      </button>
                    @else
                      <span class="badge bg-label-success">Soldé</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-5">
                    <i class="bx bx-file fs-1 d-block mb-2 opacity-25"></i>
                    Aucun bordereau à afficher.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($bordereaux->hasPages())
          <div class="mt-3">
            {{ $bordereaux->links() }}
          </div>
        @endif
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
          <div class="alert alert-info mb-2">
            <strong>Reste à payer :</strong> <span id="paiementBordereauReste">0</span> FCFA
          </div>
          <div class="alert alert-warning mb-2 d-none" id="paiementFinancementAlert">
            <strong>Financement disponible :</strong> <span id="paiementFinancementMontant">0</span> FCFA
            <br><small>Le paiement est plafonné au financement disponible.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="paiementBordereauMontant" class="form-control" required
              placeholder="Ex: 500 000" inputmode="numeric" autocomplete="off" />
            <small class="text-muted" id="paiementBordereauMontantHint">Montant maximum selon le reste et le financement.</small>
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
          <div class="mb-0">
            <label class="form-label">Commentaire</label>
            <input type="text" name="commentaire" class="form-control" placeholder="Optionnel" />
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  function formatNombre(n) {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function formatMontantSaisie(digits) {
    digits = String(digits || '').replace(/\D/g, '');
    if (!digits) return '';
    return formatNombre(parseInt(digits, 10));
  }

  var form = document.getElementById('formPaiementBordereau');
  var inputMontant = document.getElementById('paiementBordereauMontant');

  if (inputMontant) {
    inputMontant.addEventListener('input', function () {
      var digits = String(inputMontant.value || '').replace(/\D/g, '');
      var plafond = parseInt(inputMontant.getAttribute('data-plafond') || '0', 10);
      if (digits && plafond > 0 && parseInt(digits, 10) > plafond) {
        digits = String(plafond);
      }
      inputMontant.value = formatMontantSaisie(digits);
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      var plafond = parseInt(inputMontant.getAttribute('data-plafond') || '0', 10);
      var montant = parseInt(String(inputMontant.value || '').replace(/\D/g, '') || '0', 10);
      if (plafond > 0 && montant > plafond) {
        e.preventDefault();
        alert('Le montant ne peut pas dépasser ' + formatNombre(plafond) + ' FCFA.');
        inputMontant.value = formatMontantSaisie(String(plafond));
        return;
      }
      inputMontant.value = String(montant || '');
    });
  }

  document.querySelectorAll('.btn-paiement-bordereau').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var agentId = btn.getAttribute('data-agent-id');
      var bordereauId = btn.getAttribute('data-bordereau-id');
      var numero = btn.getAttribute('data-bordereau-numero');
      var reste = parseInt(btn.getAttribute('data-bordereau-reste') || '0', 10);
      var financement = parseInt(btn.getAttribute('data-financement') || '0', 10);
      var plafond = parseInt(btn.getAttribute('data-plafond') || '0', 10);

      form.action = '{{ url('/gestion-financiere/agent-financier') }}/' + agentId + '/bordereaux/' + bordereauId + '/paiement';
      document.getElementById('paiementBordereauNumero').textContent = numero || '—';
      document.getElementById('paiementBordereauReste').textContent = formatNombre(reste);

      var alertFinancement = document.getElementById('paiementFinancementAlert');
      var hint = document.getElementById('paiementBordereauMontantHint');
      if (financement > 0) {
        alertFinancement.classList.remove('d-none');
        document.getElementById('paiementFinancementMontant').textContent = formatNombre(financement);
        hint.textContent = 'Maximum : ' + formatNombre(plafond) + ' FCFA (plafonné par le financement).';
      } else {
        alertFinancement.classList.add('d-none');
        hint.textContent = 'Maximum : ' + formatNombre(plafond) + ' FCFA (reste du bordereau).';
      }

      inputMontant.setAttribute('data-plafond', String(plafond));
      inputMontant.value = plafond > 0 ? formatMontantSaisie(String(plafond)) : '';
    });
  });
});
</script>
@endsection
