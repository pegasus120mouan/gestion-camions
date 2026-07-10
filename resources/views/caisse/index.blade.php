@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Gestion de caisse</h4>
        <p class="text-muted mb-0">Caisse locale — indépendante de l’API.</p>
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

    <div class="card border-0 text-white shadow-sm mb-3" style="background-color: #0d6efd;">
      <div class="card-body text-center py-4 px-4">
        <div class="small opacity-75 text-uppercase mb-1">Montant actuel de la caisse</div>
        <div class="fw-bold display-6 mb-0">
          {{ number_format((float) $stats['solde_caisse'], 0, ',', ' ') }} FCFA
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body py-3">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#caisseApproModal">
          <i class="bx bx-plus-circle me-1"></i>Approvisionnement caisse
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bx bx-history me-1"></i>Historique des approvisionnements</span>
        <span class="text-muted">{{ $approvisionnements->total() }} opération(s)</span>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('caisse.index') }}" class="row g-2 mb-3">
          <div class="col-md-3">
            <select name="origine" class="form-select form-select-sm">
              <option value="all" @selected($filters['origine'] === 'all')>Toutes les origines</option>
              <option value="manuel" @selected($filters['origine'] === 'manuel')>Manuels</option>
              <option value="banque" @selected($filters['origine'] === 'banque')>Depuis banques</option>
              <option value="usine" @selected($filters['origine'] === 'usine')>Paiements usines</option>
            </select>
          </div>
          <div class="col-md-2">
            <input type="date" name="date_debut" class="form-control form-control-sm" value="{{ $filters['date_debut'] }}">
          </div>
          <div class="col-md-2">
            <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ $filters['date_fin'] }}">
          </div>
          <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher..."
              value="{{ $filters['search'] }}">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bx bx-search"></i></button>
            @if($filters['origine'] !== 'all' || $filters['search'] !== '' || $filters['date_debut'] || $filters['date_fin'])
              <a href="{{ route('caisse.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-x"></i>
              </a>
            @endif
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th class="text-end">Montant</th>
                <th>Origine</th>
                <th>Motif</th>
                <th class="text-end">Solde après</th>
                <th>Utilisateur</th>
              </tr>
            </thead>
            <tbody>
              @forelse($approvisionnements as $appro)
                @php
                  $source = (string) ($appro->source ?? '');
                  $isUsine = str_starts_with($source, 'Usine:');
                  $isBanque = str_starts_with($source, 'Banque:');
                  $labelSource = $isUsine
                    ? trim(substr($source, strlen('Usine:')))
                    : ($isBanque ? trim(substr($source, strlen('Banque:'))) : $source);
                  $userName = trim(($appro->user->name ?? '').' '.($appro->user->prenom ?? ''));
                @endphp
                <tr>
                  <td>{{ $appro->date_mouvement?->format('d/m/Y H:i') ?? '—' }}</td>
                  <td class="text-end fw-semibold text-success">
                    {{ number_format((float) $appro->montant, 0, ',', ' ') }} FCFA
                  </td>
                  <td>
                    @if($isUsine)
                      <span class="badge bg-info">Usine</span>
                    @elseif($isBanque)
                      <span class="badge bg-warning text-dark">Banque</span>
                    @else
                      <span class="badge bg-secondary">Manuel</span>
                    @endif
                    {{ $labelSource !== '' ? $labelSource : '—' }}
                  </td>
                  <td>{{ $appro->motifs ?: '—' }}</td>
                  <td class="text-end">{{ number_format((float) $appro->solde_apres, 0, ',', ' ') }} FCFA</td>
                  <td>{{ $userName !== '' ? $userName : '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    Aucun approvisionnement enregistré.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($approvisionnements->hasPages())
          <div class="d-flex justify-content-center mt-3">
            {{ $approvisionnements->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="caisseApproModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('caisse.approvisionnement.store') }}" id="formCaisseAppro">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Approvisionnement caisse</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Montant <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="text" name="montant" id="caisseMontant" class="form-control @error('montant') is-invalid @enderror"
                inputmode="numeric" autocomplete="off" placeholder="Ex : 500 000" required
                value="{{ old('montant') }}">
              <span class="input-group-text">FCFA</span>
            </div>
            @error('montant')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Origine / source</label>
            <input type="text" name="source" class="form-control" placeholder="Ex : SIB, Espèces…"
              value="{{ old('source') }}">
          </div>
          <div class="mb-0">
            <label class="form-label">Motif</label>
            <input type="text" name="motifs" class="form-control"
              placeholder="Approvisionnement de la caisse"
              value="{{ old('motifs', 'Approvisionnement de la caisse') }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('caisseMontant');
  var form = document.getElementById('formCaisseAppro');

  function formatMontant(v) {
    var digits = String(v || '').replace(/\D/g, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  if (input) {
    input.value = formatMontant(input.value);
    input.addEventListener('input', function () {
      input.value = formatMontant(input.value);
    });
  }

  if (form && input) {
    form.addEventListener('submit', function () {
      input.value = String(input.value || '').replace(/\D/g, '');
    });
  }

  @if($errors->has('montant'))
    new bootstrap.Modal(document.getElementById('caisseApproModal')).show();
  @endif
});
</script>
@endsection
