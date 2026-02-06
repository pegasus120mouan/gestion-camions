@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des depenses</h4>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Vehicule</th>
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
                  <a href="{{ route('vehicules.depenses', ['vehicule_id' => $d->vehicule_id, 'matricule' => $d->matricule_vehicule]) }}">
                    {{ $d->matricule_vehicule }}
                  </a>
                </td>
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
                <td colspan="5" class="text-center">Aucune depense</td>
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
        {{ $depenses->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
