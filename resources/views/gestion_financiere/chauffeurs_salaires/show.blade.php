@extends('layout.main')
@section('content')
@include('gestion_financiere._table_financiere_styles')
@php
  $nomComplet = trim($chauffeur->nom . ' ' . $chauffeur->prenoms);
  $anneeRetour = (int) request('annee', now()->year);
  $moisRetour = (int) request('mois', now()->month);
  $totalImpaye = $impayees->sum('reste');
  $urlRetour = route('gestionfinanciere.chauffeurs_salaires.index', ['annee' => $anneeRetour, 'mois' => $moisRetour]);
@endphp
<div class="content-wrapper gf-financier-page">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="mb-1">Salaire — {{ $nomComplet ?: 'Chauffeur' }}</h4>
        @if($chauffeur->matricule_vehicule)
          <span class="badge bg-label-secondary me-1">{{ $chauffeur->matricule_vehicule }}</span>
        @endif
        @if($chauffeur->contact)
          <span class="badge bg-secondary">{{ $chauffeur->contact }}</span>
        @endif
        @if($chauffeur->salaire)
          <span class="badge bg-label-primary ms-1">Salaire mensuel : {{ number_format((float) $chauffeur->salaire, 0, ',', ' ') }} FCFA</span>
        @endif
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAvanceChauffeur">
          <i class="bx bx-wallet me-1"></i>Avance
        </button>
        @if($impayees->count() > 0)
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPaiementChauffeur">
            <i class="bx bx-money me-1"></i>Payer salaire(s)
          </button>
        @endif
        <a href="{{ $urlRetour }}" class="btn btn-secondary btn-sm">
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
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1" style="color: #664d03;">Total reste à payer</h6>
            <h3 class="mb-0" style="color: #664d03;">{{ number_format($totalImpaye, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">{{ $impayees->count() }} mois avec solde</small>
          </div>
        </div>
      </div>
      <div class="col-md-8">
        <div class="alert alert-info mb-0 h-100 d-flex align-items-center">
          <div>
            <strong>Avances reportées :</strong> si une avance dépasse le salaire du mois, le surplus est déduit du mois suivant.
            <br><strong>Paiement cumulé :</strong> plusieurs mois peuvent être réglés en une seule opération (ex. janvier + février payés en mars).
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header" style="background-color: #eef1f4;">
        <h5 class="card-title mb-0"><i class="bx bx-calendar me-2"></i>Situation mois par mois</h5>
      </div>
      <div class="table-responsive gf-table-wrap">
        <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
          <thead>
            <tr>
              <th>Mois</th>
              <th class="text-end">Salaire dû</th>
              <th class="text-end">Avances</th>
              <th class="text-end">Payé</th>
              <th class="text-end">Reste</th>
              <th class="text-center">Statut</th>
            </tr>
          </thead>
          <tbody>
            @forelse($periodes as $row)
              <tr @class(['table-warning' => $row['reste'] > 0])>
                <td class="fw-semibold">{{ ucfirst($row['libelle']) }}</td>
                <td class="text-end">{{ number_format($row['du'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-info">
                  @php $avancesReportees = (float) ($row['avances_reportees'] ?? 0); @endphp
                  @if($avancesReportees > 0 || $row['avances'] > 0)
                    {{ number_format($row['avances'] + $avancesReportees, 0, ',', ' ') }} FCFA
                    @if($avancesReportees > 0)
                      <br><small class="text-muted">dont {{ number_format($avancesReportees, 0, ',', ' ') }} reporté(s)</small>
                    @endif
                  @else
                    <span class="text-muted">0 FCFA</span>
                  @endif
                </td>
                <td class="text-end text-success">{{ number_format($row['paye'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end">
                  @if($row['reste'] > 0)
                    <span class="text-danger fw-bold">{{ number_format($row['reste'], 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-muted">0 FCFA</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($row['reste'] <= 0)
                    <span class="badge bg-success">Soldé</span>
                  @elseif($row['paye'] > 0 || $row['avances'] > 0 || ($row['avances_reportees'] ?? 0) > 0)
                    <span class="badge bg-warning text-dark">Partiel</span>
                  @else
                    <span class="badge bg-danger">Impayé</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">Aucune période enregistrée.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header" style="background-color: #cff4fc;">
            <h5 class="card-title mb-0"><i class="bx bx-wallet me-2"></i>Historique avances</h5>
          </div>
          <div class="table-responsive gf-table-wrap">
            <table class="table table-sm table-bordered align-middle gf-table-financier mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Mois concerné</th>
                  <th>Libellé</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($avances as $avance)
                  <tr>
                    <td>{{ $avance->date_avance?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                      @if($avance->periode)
                        {{ ucfirst(\Carbon\Carbon::createFromDate($avance->periode->annee, $avance->periode->mois, 1)->locale('fr')->translatedFormat('F Y')) }}
                      @else
                        —
                      @endif
                    </td>
                    <td>{{ $avance->libelle ?: 'Avance sur salaire' }}</td>
                    <td class="text-end text-info fw-bold">{{ number_format((float) $avance->montant, 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-3">Aucune avance.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header" style="background-color: #d1e7dd;">
            <h5 class="card-title mb-0"><i class="bx bx-money me-2"></i>Historique paiements</h5>
          </div>
          <div class="table-responsive gf-table-wrap">
            <table class="table table-sm table-bordered align-middle gf-table-financier mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Libellé</th>
                  <th>Mois couverts</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($paiements as $paiement)
                  @php
                    $moisCouverts = $paiement->periodes->map(function ($ligne) {
                      $p = $ligne->periode;
                      if (!$p) {
                        return null;
                      }
                      return ucfirst(\Carbon\Carbon::createFromDate($p->annee, $p->mois, 1)->locale('fr')->translatedFormat('M Y'));
                    })->filter()->implode(', ');
                  @endphp
                  <tr>
                    <td>{{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                      {{ $paiement->libelle ?: 'Paiement salaire' }}
                      @if($paiement->commentaire)
                        <br><small class="text-muted">{{ $paiement->commentaire }}</small>
                      @endif
                    </td>
                    <td><small>{{ $moisCouverts ?: '—' }}</small></td>
                    <td class="text-end text-success fw-bold">{{ number_format((float) $paiement->montant_total, 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-3">Aucun paiement.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAvanceChauffeur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-wallet me-2"></i>Avance sur salaire</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('gestionfinanciere.chauffeurs_salaires.avance.store', $chauffeur) }}">
        @csrf
        <div class="modal-body">
          <div class="alert alert-secondary mb-3">
            <strong>Chauffeur :</strong> {{ $nomComplet }}
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Mois concerné <span class="text-danger">*</span></label>
              <select name="mois" class="form-select" required>
                @for($m = 1; $m <= 12; $m++)
                  <option value="{{ $m }}" @selected($moisRetour === $m)>
                    {{ \Carbon\Carbon::createFromDate($anneeRetour, $m, 1)->locale('fr')->translatedFormat('F') }}
                  </option>
                @endfor
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Année <span class="text-danger">*</span></label>
              <select name="annee" class="form-select" required>
                @for($a = now()->year + 1; $a >= now()->year - 3; $a--)
                  <option value="{{ $a }}" @selected($anneeRetour === $a)>{{ $a }}</option>
                @endfor
              </select>
            </div>
          </div>
          <div class="mb-3 mt-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="montant" class="form-control" min="1" step="1" required placeholder="Ex: 50000">
          </div>
          <div class="mb-3">
            <label class="form-label">Date avance <span class="text-danger">*</span></label>
            <input type="date" name="date_avance" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Libellé</label>
            <input type="text" name="libelle" class="form-control" placeholder="Avance sur salaire" value="Avance sur salaire">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">
            <i class="bx bx-save me-1"></i>Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($impayees->count() > 0)
<div class="modal fade" id="modalPaiementChauffeur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement salaire(s)</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('gestionfinanciere.chauffeurs_salaires.paiement.store', $chauffeur) }}" id="formPaiementChauffeur">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            Cochez un ou plusieurs mois à régler. Le montant total correspond à la somme des restes dus
            (après déduction des avances déjà versées).
          </div>
          <div class="table-responsive gf-table-wrap mb-3">
            <table class="table table-sm table-bordered gf-table-financier mb-0">
              <thead>
                <tr>
                  <th class="text-center" style="width: 50px;">
                    <input type="checkbox" class="form-check-input" id="checkAllPeriodes" title="Tout sélectionner">
                  </th>
                  <th>Mois</th>
                  <th class="text-end">Reste à payer</th>
                </tr>
              </thead>
              <tbody>
                @foreach($impayees as $row)
                  <tr>
                    <td class="text-center">
                      <input type="checkbox"
                        class="form-check-input periode-check"
                        name="periode_ids[]"
                        value="{{ $row['periode']->id }}"
                        data-reste="{{ (int) round($row['reste']) }}">
                    </td>
                    <td>{{ ucfirst($row['libelle']) }}</td>
                    <td class="text-end text-danger fw-bold">{{ number_format($row['reste'], 0, ',', ' ') }} FCFA</td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="2" class="text-end fw-bold">Total sélectionné</td>
                  <td class="text-end fw-bold text-primary" id="totalPaiementSelection">0 FCFA</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement <span class="text-danger">*</span></label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Libellé</label>
            <input type="text" name="libelle" class="form-control" placeholder="Généré automatiquement si vide (ex. Salaire janvier 2026 + février 2026)">
          </div>
          <div class="mb-3">
            <label class="form-label">Commentaire</label>
            <input type="text" name="commentaire" class="form-control" placeholder="Optionnel">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" id="btnSubmitPaiement" disabled>
            <i class="bx bx-save me-1"></i>Enregistrer le paiement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection

@section('page-scripts')
@if($impayees->count() > 0)
<script>
(function () {
  var checks = document.querySelectorAll('.periode-check');
  var checkAll = document.getElementById('checkAllPeriodes');
  var totalEl = document.getElementById('totalPaiementSelection');
  var submitBtn = document.getElementById('btnSubmitPaiement');

  function formatFcfa(n) {
    return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
  }

  function updateTotal() {
    var total = 0;
    var any = false;
    checks.forEach(function (cb) {
      if (cb.checked) {
        any = true;
        total += parseInt(cb.getAttribute('data-reste') || '0', 10);
      }
    });
    if (totalEl) {
      totalEl.textContent = formatFcfa(total);
    }
    if (submitBtn) {
      submitBtn.disabled = !any;
    }
    if (checkAll) {
      checkAll.checked = any && Array.from(checks).every(function (cb) { return cb.checked; });
      checkAll.indeterminate = any && !checkAll.checked;
    }
  }

  checks.forEach(function (cb) {
    cb.addEventListener('change', updateTotal);
  });

  if (checkAll) {
    checkAll.addEventListener('change', function () {
      checks.forEach(function (cb) {
        cb.checked = checkAll.checked;
      });
      updateTotal();
    });
  }

  updateTotal();
})();
</script>
@endif
@endsection
