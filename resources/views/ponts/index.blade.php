@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Ponts de pesage</h4>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Stock disponible</th>
              <th>Gerant</th>
              <th>Cooperatif</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($ponts as $p)
              <tr>
                <td>{{ $p['code_pont'] ?? '' }}</td>
                <td>
                  <a href="{{ route('ponts.stock', ['id_pont' => $p['id_pont'] ?? 0]) }}" class="text-primary fw-bold text-decoration-none">
                    {{ $p['nom_pont'] ?? '' }}
                  </a>
                </td>
                <td>
                  <strong>{{ number_format((float)($p['stock_disponible'] ?? 0), 0, ',', ' ') }} kg</strong>
                </td>
                <td>{{ $p['gerant'] ?? '' }}</td>
                <td>{{ $p['cooperatif'] ?? '-' }}</td>
                <td>
                  @if(($p['statut'] ?? '') === 'Actif')
                    <span class="badge bg-success">Actif</span>
                  @else
                    <span class="badge bg-secondary">Inactif</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">Aucun pont</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(count($ponts) > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Resume</h5>
          <p><strong>Total ponts:</strong> {{ count($ponts) }}</p>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
