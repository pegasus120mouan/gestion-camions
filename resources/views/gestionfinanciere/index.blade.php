@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Soldes</h4>
    </div>

    <div class="row g-3">
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <div class="text-muted">Solde actuel</div>
            <div class="display-6 mb-0">{{ number_format($solde, 2, ',', ' ') }}</div>
          </div>
        </div>

        <div class="card mt-3">
          <div class="card-body">
            <h6 class="mb-3">Ajouter un mouvement</h6>
            <form method="POST" action="{{ route('gestionfinanciere.store') }}">
              @csrf

              <div class="mb-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                  <option value="deposit" {{ old('type') === 'deposit' ? 'selected' : '' }}>Dépôt</option>
                  <option value="withdraw" {{ old('type') === 'withdraw' ? 'selected' : '' }}>Retrait</option>
                </select>
                @error('type')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>

              <div class="mb-3">
                <label class="form-label">Montant</label>
                <input type="text" name="montant" inputmode="numeric" class="form-control" value="{{ old('montant') }}" required />
                @error('montant')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>

              <div class="mb-3">
                <label class="form-label">Note</label>
                <input type="text" name="note" class="form-control" value="{{ old('note') }}" />
                @error('note')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>

              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <h6 class="mb-3">Historique des mouvements</h6>

            <div class="table-responsive text-nowrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th class="text-end">Montant</th>
                    <th>Note</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($mouvements as $m)
                    <tr>
                      <td>{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                      <td>{{ $m->type === 'deposit' ? 'Dépôt' : 'Retrait' }}</td>
                      <td class="text-end">{{ number_format($m->montant, 2, ',', ' ') }}</td>
                      <td>{{ $m->note }}</td>
                      <td class="text-end">
                        <form method="POST" action="{{ route('gestionfinanciere.destroy', $m) }}" class="d-inline" onsubmit="return confirm('Supprimer ce mouvement ?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center">Aucun mouvement</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              {{ $mouvements->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var input = document.querySelector('input[name="montant"]');
    if (!input) return;

    function formatThousandsSpaces(value) {
      var raw = String(value || '');
      raw = raw.replace(/\u00A0/g, ' ').replace(/\s+/g, '');
      if (raw === '') return '';
      raw = raw.replace(/,/g, '.');
      raw = raw.replace(/[^0-9.]/g, '');
      var parts = raw.split('.');
      var intPart = parts[0] || '';
      var decPart = parts.length > 1 ? parts.slice(1).join('') : '';
      intPart = intPart.replace(/^0+(?=\d)/, '');
      var formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
      return decPart !== '' ? formattedInt + '.' + decPart : formattedInt;
    }

    input.addEventListener('input', function () {
      var start = input.selectionStart;
      var before = input.value;
      input.value = formatThousandsSpaces(before);
      try {
        input.setSelectionRange(start, start);
      } catch (e) {}
    });

    input.value = formatThousandsSpaces(input.value);
  });
</script>
@endsection
