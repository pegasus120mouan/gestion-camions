@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Demande de sortie</h4>
    </div>

    <div class="row g-3">
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h6 class="mb-3">Nouvelle demande</h6>
            <form method="POST" action="{{ route('gestionfinanciere.sorties.store') }}">
              @csrf

              <div class="mb-3">
                <label class="form-label">Montant</label>
                <input type="text" name="montant" inputmode="numeric" class="form-control" value="{{ old('montant') }}" required />
                @error('montant')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>

              <div class="mb-3">
                <label class="form-label">Motif</label>
                <textarea name="motif" class="form-control" rows="4" required>{{ old('motif') }}</textarea>
                @error('motif')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>

              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Envoyer</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <h6 class="mb-3">Liste des demandes</h6>

            <div class="table-responsive text-nowrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>N°</th>
                    <th>Date</th>
                    <th class="text-end">Montant</th>
                    <th>Statut</th>
                    <th class="text-end">Payé</th>
                    <th class="text-end">Reste</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($demandes as $d)
                    <tr>
                      <td>{{ $d->numero_demande }}</td>
                      <td>{{ $d->date_demande?->format('d/m/Y H:i') }}</td>
                      <td class="text-end">{{ number_format($d->montant, 2, ',', ' ') }}</td>
                      <td>{{ $d->statut }}</td>
                      <td class="text-end">{{ $d->montant_payer === null ? '-' : number_format($d->montant_payer, 2, ',', ' ') }}</td>
                      <td class="text-end">{{ $d->montant_reste === null ? '-' : number_format($d->montant_reste, 2, ',', ' ') }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center">Aucune demande</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              {{ $demandes->links() }}
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
