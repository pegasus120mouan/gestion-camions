@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Depenses du vehicule</h4>
        @if(isset($vehicule) && is_array($vehicule))
          <p class="text-muted mb-0">{{ $vehicule['matricule_vehicule'] ?? '' }}</p>
        @endif
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelleDepense">
        Nouvelle depense
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Description</th>
              <th>Montant</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($depenses as $d)
              <tr>
                <td>
                  @php
                    $type = $d->type_depense ?? '';
                  @endphp
                  @if($type === 'carburant')
                    <span class="badge bg-warning">Carburant</span>
                  @elseif($type === 'pieces')
                    <span class="badge bg-info">Pieces</span>
                  @elseif($type === 'entretien')
                    <span class="badge bg-primary">Entretien</span>
                  @elseif($type === 'reparation')
                    <span class="badge bg-danger">Reparation</span>
                  @else
                    <span class="badge bg-secondary">{{ $type }}</span>
                  @endif
                </td>
                <td>{{ $d->description ?? '-' }}</td>
                <td>{{ number_format((float)($d->montant ?? 0), 0, ',', ' ') }} FCFA</td>
                <td>
                  @if($d->date_depense)
                    {{ $d->date_depense->format('d-m-Y') }}
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center">Aucune depense</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($depenses->count() > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Resume</h5>
          @php
            $totalDepenses = $depenses->sum('montant');
          @endphp
          <p><strong>Total depenses (page):</strong> <span class="text-danger">{{ number_format($totalDepenses, 0, ',', ' ') }} FCFA</span></p>
        </div>
      </div>
    @endif

    @if($depenses->hasPages())
      <div class="mt-4 d-flex justify-content-center">
        {{ $depenses->appends(['matricule' => $vehicule['matricule_vehicule'] ?? ''])->links() }}
      </div>
    @endif

    <div class="mt-3">
      <a href="{{ route('camions.index') }}" class="btn btn-outline-secondary">Retour aux camions</a>
    </div>
  </div>
</div>

<div class="modal fade" id="modalNouvelleDepense" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nouvelle depense</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('vehicules.depenses.store', ['vehicule_id' => $vehicule['vehicules_id'] ?? 0]) }}">
          @csrf
          <input type="hidden" name="matricule_vehicule" value="{{ $vehicule['matricule_vehicule'] ?? '' }}" />

          <div class="mb-3">
            <label class="form-label">Type de depense</label>
            <select name="type_depense" class="form-select" required>
              <option value="">-- Choisir --</option>
              <option value="carburant">Carburant</option>
              <option value="pieces">Achat de pieces</option>
              <option value="entretien">Entretien</option>
              <option value="reparation">Reparation</option>
              <option value="autre">Autre</option>
            </select>
            @error('type_depense')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Details de la depense..."></textarea>
            @error('description')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="montant_input" class="form-control" required placeholder="200 000" />
            @error('montant')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date_depense" class="form-control" value="{{ date('Y-m-d') }}" required />
            @error('date_depense')<div class="text-danger mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@if ($errors->any())
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var el = document.getElementById('modalNouvelleDepense');
      if (el && window.bootstrap) {
        new bootstrap.Modal(el).show();
      }
    });
  </script>
@endif

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var montantInput = document.getElementById('montant_input');
    if (montantInput) {
      montantInput.addEventListener('input', function (e) {
        var value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
        if (value) {
          e.target.value = parseInt(value, 10).toLocaleString('fr-FR').replace(/,/g, ' ');
        }
      });

      montantInput.form.addEventListener('submit', function () {
        montantInput.value = montantInput.value.replace(/\s/g, '');
      });
    }
  });
</script>
@endsection
