@extends('layout.main')
@section('content')
@include('gestion_financiere._table_financiere_styles')
<div class="content-wrapper gf-financier-page">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="mb-1">Gestion Salaires Chauffeurs</h4>
        <span class="badge bg-label-primary">{{ $groupePgf?->nom_groupe ?? 'Chauffeurs PGF' }}</span>
        <small class="text-muted ms-2">Suivi mensuel, avances et paiements cumulés</small>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('gestionfinanciere.chauffeurs_salaires.index') }}" class="row g-3 align-items-end">
          <div class="col-auto">
            <label class="form-label mb-1">Mois</label>
            <select name="mois" class="form-select">
              @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($mois === $m)>
                  {{ \Carbon\Carbon::createFromDate($annee, $m, 1)->locale('fr')->translatedFormat('F') }}
                </option>
              @endfor
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label mb-1">Année</label>
            <select name="annee" class="form-select">
              @for($a = now()->year + 1; $a >= now()->year - 3; $a--)
                <option value="{{ $a }}" @selected($annee === $a)>{{ $a }}</option>
              @endfor
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-filter-alt me-1"></i>Afficher
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1" style="color: #842029;">Salaire du mois</h6>
            <h4 class="mb-0" style="color: #842029;">{{ number_format($totaux['du'], 0, ',', ' ') }} FCFA</h4>
            <small class="text-muted">{{ ucfirst($libelleMois) }}</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card" style="background-color: #cff4fc; border-left: 4px solid #0dcaf0;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1" style="color: #055160;">Avances</h6>
            <h4 class="mb-0" style="color: #055160;">{{ number_format($totaux['avances'], 0, ',', ' ') }} FCFA</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1" style="color: #0f5132;">Payé</h6>
            <h4 class="mb-0" style="color: #0f5132;">{{ number_format($totaux['paye'], 0, ',', ' ') }} FCFA</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1" style="color: #664d03;">Reste</h6>
            <h4 class="mb-0" style="color: #664d03;">{{ number_format($totaux['reste'], 0, ',', ' ') }} FCFA</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #eef1f4;">
        <h5 class="card-title mb-0">
          <i class="bx bx-user me-2"></i>Chauffeurs — {{ ucfirst($libelleMois) }}
        </h5>
        <span class="badge bg-secondary">{{ count($lignes) }} chauffeur(s)</span>
      </div>
      <div class="table-responsive gf-table-wrap">
        <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
          <thead>
            <tr>
              <th>Chauffeur</th>
              <th>Camion</th>
              <th class="text-end">Salaire</th>
              <th class="text-end">Avances</th>
              <th class="text-end">Payé</th>
              <th class="text-end">Reste</th>
              <th class="text-center">Autres mois dus</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($lignes as $ligne)
              @php
                $chauffeur = $ligne['chauffeur'];
                $soldes = $ligne['soldes'];
                $nomComplet = trim($chauffeur->nom . ' ' . $chauffeur->prenoms);
                $impayeesAutres = $ligne['impayees']->filter(
                  fn ($row) => (int) $row['periode']->annee !== $annee || (int) $row['periode']->mois !== $mois
                );
                $lienShow = route('gestionfinanciere.chauffeurs_salaires.show', [
                  'chauffeur' => $chauffeur->id,
                  'annee' => $annee,
                  'mois' => $mois,
                ]);
              @endphp
              <tr>
                <td>
                  <a href="{{ $lienShow }}" class="fw-bold text-primary text-decoration-none">
                    {{ $nomComplet ?: '—' }}
                  </a>
                  @if($chauffeur->contact)
                    <br><small class="text-muted">{{ $chauffeur->contact }}</small>
                  @endif
                </td>
                <td>
                  @if($chauffeur->matricule_vehicule)
                    <span class="badge bg-label-secondary">{{ $chauffeur->matricule_vehicule }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end">{{ number_format($soldes['du'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end text-info">
                  @php $avancesReportees = (float) ($soldes['avances_reportees'] ?? 0); @endphp
                  @if($avancesReportees > 0 || $soldes['avances'] > 0)
                    {{ number_format($soldes['avances'] + $avancesReportees, 0, ',', ' ') }} FCFA
                    @if($avancesReportees > 0)
                      <br><small class="text-muted">dont {{ number_format($avancesReportees, 0, ',', ' ') }} reporté(s)</small>
                    @endif
                  @else
                    <span class="text-muted">0 FCFA</span>
                  @endif
                </td>
                <td class="text-end text-success">{{ number_format($soldes['paye'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end">
                  @if($soldes['reste'] > 0)
                    <span class="text-danger fw-bold">{{ number_format($soldes['reste'], 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-success"><i class="bx bx-check-circle"></i> Soldé</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($impayeesAutres->count() > 0)
                    <span class="badge bg-warning text-dark" title="{{ $impayeesAutres->pluck('libelle')->implode(', ') }}">
                      {{ $impayeesAutres->count() }} mois
                    </span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-center">
                  <a href="{{ $lienShow }}" class="btn btn-sm btn-outline-primary" title="Avances et paiements">
                    <i class="bx bx-wallet"></i> Gérer
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  Aucun chauffeur dans le groupe « Chauffeurs PGF ».
                  <br><small>Assignez des chauffeurs au groupe depuis la fiche Chauffeurs.</small>
                </td>
              </tr>
            @endforelse
          </tbody>
          @if(count($lignes) > 0)
            <tfoot>
              <tr>
                <td colspan="2" class="text-end fw-bold">Totaux</td>
                <td class="text-end fw-bold">{{ number_format($totaux['du'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end fw-bold">{{ number_format($totaux['avances'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end fw-bold">{{ number_format($totaux['paye'], 0, ',', ' ') }} FCFA</td>
                <td class="text-end fw-bold">{{ number_format($totaux['reste'], 0, ',', ' ') }} FCFA</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
