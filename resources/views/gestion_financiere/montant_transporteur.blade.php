@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Gestion financière /</span> Montant Transporteur
    </h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-truck me-2"></i>Liste des Transporteurs</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Transporteur</th>
              <th>Code</th>
              <th class="text-center">Camions</th>
              <th class="text-end">Montant dû</th>
              <th class="text-end">Montant payé</th>
              <th class="text-end">Reste à payer</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $item)
              <tr>
                <td>
                  <a href="{{ route('gestionfinanciere.transporteur.show', $item['transporteur']) }}" class="text-primary fw-bold">
                    {{ $item['transporteur']->nom }} {{ $item['transporteur']->prenoms }}
                  </a>
                </td>
                <td>
                  <span class="badge bg-label-primary">{{ $item['transporteur']->code }}</span>
                </td>
                <td class="text-center">{{ $item['transporteur']->vehicules_count }}</td>
                <td class="text-end">
                  <span class="text-primary fw-bold">{{ number_format($item['montant_du'], 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-end">
                  <span class="text-success">{{ number_format($item['montant_paye'], 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-end">
                  @if($item['reste_a_payer'] > 0)
                    <span class="text-danger fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @elseif($item['reste_a_payer'] < 0)
                    <span class="text-warning fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-success"><i class="bx bx-check-circle"></i> Soldé</span>
                  @endif
                </td>
                <td class="text-center">
                  <a href="{{ route('gestionfinanciere.transporteur.show', $item['transporteur']) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-show"></i>
                  </a>
                  <a href="{{ route('transporteurs.show', $item['transporteur']) }}" class="btn btn-sm btn-outline-secondary" title="Fiche transporteur">
                    <i class="bx bx-user"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Aucun transporteur enregistré</td>
              </tr>
            @endforelse
          </tbody>
          @if(count($data) > 0)
          <tfoot class="table-light">
            <tr>
              <th colspan="3" class="text-end">TOTAUX</th>
              <th class="text-end text-primary fw-bold">{{ number_format(collect($data)->sum('montant_du'), 0, ',', ' ') }} FCFA</th>
              <th class="text-end text-success">{{ number_format(collect($data)->sum('montant_paye'), 0, ',', ' ') }} FCFA</th>
              <th class="text-end text-danger fw-bold">{{ number_format(collect($data)->sum('reste_a_payer'), 0, ',', ' ') }} FCFA</th>
              <th></th>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
