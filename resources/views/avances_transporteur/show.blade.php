@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h4 class="fw-bold mb-0">
        <span class="text-muted fw-light">Avances transporteurs /</span>
        {{ $transporteur->code }} — {{ strtoupper(trim($transporteur->nom.' '.$transporteur->prenoms)) }}
      </h4>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAvanceTransporteur">
          <i class="bx bx-plus me-1"></i>Ajouter une avance
        </button>
        <a href="{{ route('avances_transporteur.index') }}" class="btn btn-outline-secondary">
          <i class="bx bx-arrow-back me-1"></i>Retour
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('avances_transporteur.show', $transporteur) }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label for="date_debut" class="form-label">Date de début</label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $filters['date_debut'] ?? '' }}">
          </div>
          <div class="col-md-4">
            <label for="date_fin" class="form-label">Date de fin</label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $filters['date_fin'] ?? '' }}">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bx bx-filter me-1"></i>Filtrer</button>
            <a href="{{ route('avances_transporteur.show', $transporteur) }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center"
        style="background-color: #d1e7dd; border-bottom: 1px solid #badbcc;">
        <h5 class="card-title mb-0" style="color: #0f5132;">
          <i class="bx bx-wallet me-2"></i>Avances ({{ $avances->count() }})
        </h5>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAvanceTransporteur">
          <i class="bx bx-plus me-1"></i>Ajouter une avance
        </button>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Mode</th>
              <th>Référence</th>
              <th>Commentaire</th>
              <th class="text-end">Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($avances as $avance)
              <tr>
                <td>{{ $avance->date_avance?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $avance->mode_paiement ?: '—' }}</td>
                <td>{{ $avance->reference ?: '—' }}</td>
                <td>{{ $avance->commentaire ?: '—' }}</td>
                <td class="text-end fw-semibold text-success">
                  {{ number_format((float) $avance->montant, 0, ',', ' ') }} FCFA
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Aucune avance enregistrée pour ce transporteur.</td>
              </tr>
            @endforelse
          </tbody>
          @if($avances->isNotEmpty())
            <tfoot>
              <tr class="fw-bold">
                <td colspan="4">Total avances</td>
                <td class="text-end text-success">{{ number_format($totalAvances, 0, ',', ' ') }} FCFA</td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    <div class="modal fade" id="modalAvanceTransporteur" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title text-white"><i class="bx bx-wallet me-2"></i>Nouvelle avance</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="{{ route('avances_transporteur.store') }}" id="formAvanceTransporteur">
            @csrf
            <input type="hidden" name="transporteur_id" value="{{ $transporteur->id }}">
            <div class="modal-body">
              <div class="alert alert-info">
                <strong>Transporteur :</strong> {{ $transporteur->code }} — {{ $transporteur->nom }} {{ $transporteur->prenoms }}
              </div>
              <div class="mb-3">
                <label class="form-label">Montant avance (FCFA) <span class="text-danger">*</span></label>
                <input type="text" name="montant" id="avanceTransporteurMontant" class="form-control" required
                  placeholder="Ex: 500 000" inputmode="numeric" autocomplete="off">
              </div>
              <div class="mb-3">
                <label class="form-label">Date de l’avance <span class="text-danger">*</span></label>
                <input type="date" name="date_avance" class="form-control" required value="{{ date('Y-m-d') }}">
              </div>
              <div class="mb-3">
                <label class="form-label">Mode de paiement</label>
                <select name="mode_paiement" class="form-select">
                  <option value="Espèces">Espèces</option>
                  <option value="Virement">Virement</option>
                  <option value="Chèque">Chèque</option>
                  <option value="Mobile Money">Mobile Money</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Référence</label>
                <input type="text" name="reference" class="form-control" placeholder="Optionnel">
              </div>
              <div class="mb-3">
                <label class="form-label">Commentaire</label>
                <input type="text" name="commentaire" class="form-control" value="Avance">
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
  </div>
</div>
@endsection

@section('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('formAvanceTransporteur');
  var input = document.getElementById('avanceTransporteurMontant');

  if (form && input) {
    form.addEventListener('submit', function () {
      input.value = String(input.value || '').replace(/\s/g, '');
    });

    input.addEventListener('input', function () {
      var digits = String(this.value || '').replace(/\D/g, '');
      this.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    });
  }
});
</script>
@endsection
