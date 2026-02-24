@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Camions</h4>
    </div>

    <form method="GET" action="{{ route('camions.index') }}" class="mb-4">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Rechercher (immatriculation, type)" value="{{ request('q') }}" />
        <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        @if(is_array($external_camions))
          <table class="table">
            <thead>
              <tr>
                <th>Immatriculation</th>
                <th>Type véhicule</th>
                <th>Date création</th>
                <th>Dépenses</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            @php
              $page = (int) request()->get('page', 1);
              $perPage = 20;
              $offset = ($page - 1) * $perPage;
              $paginatedCamions = array_slice($external_camions, $offset, $perPage);
            @endphp
            <tbody class="table-border-bottom-0">
              @forelse($paginatedCamions as $v)
                <tr>
                  <td>
                    <a href="{{ route('vehicules.depenses', ['vehicule_id' => $v['vehicules_id'] ?? 0, 'matricule' => $v['matricule_vehicule'] ?? '']) }}">
                      {{ $v['matricule_vehicule'] ?? '' }}
                    </a>
                  </td>
                  <td>
                    @php $typeVehicule = strtolower($v['type_vehicule'] ?? ''); @endphp
                    @if($typeVehicule === 'voiture')
                      <i class="bx bxs-truck text-primary" style="font-size: 1.5rem;"></i>
                    @elseif($typeVehicule === 'moto')
                      <i class="bx bx-cycling text-success" style="font-size: 1.5rem;"></i>
                    @else
                      {{ $v['type_vehicule'] ?? '-' }}
                    @endif
                  </td>
                  <td>
                    @php
                      $dateCreated = $v['created_at'] ?? '';
                      if ($dateCreated) {
                        try {
                          $dateCreated = \Carbon\Carbon::parse($dateCreated)->format('d-m-Y');
                        } catch (\Exception $e) {}
                      }
                    @endphp
                    {{ $dateCreated ?: '-' }}
                  </td>
                  <td>
                    @php
                      $vehiculeId = $v['vehicules_id'] ?? 0;
                      $depenseTotal = \App\Models\Depense::where('vehicule_id', $vehiculeId)->sum('montant');
                    @endphp
                    {{ number_format($depenseTotal, 0, ',', ' ') }} FCFA
                  </td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('vehicules.depenses', ['vehicule_id' => $v['vehicules_id'] ?? 0, 'matricule' => $v['matricule_vehicule'] ?? '']) }}">
                      <i class="bx bx-show"></i> Détails
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center">Aucun camion</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        @endif
      </div>
    </div>

    @if(is_array($external_camions))
      @php
        $page = (int) request()->get('page', 1);
        $perPage = 20;
        $total = count($external_camions);
        $lastPage = (int) ceil($total / $perPage);
      @endphp
      @if($lastPage > 1)
        <div class="d-flex justify-content-center align-items-center mt-4 p-3 bg-white rounded shadow-sm">
          <nav class="d-flex align-items-center gap-2">
            <a href="{{ route('camions.index', array_merge(request()->query(), ['page' => max(1, $page - 1)])) }}" 
               class="btn btn-sm btn-outline-secondary {{ $page <= 1 ? 'disabled' : '' }}">
              <i class="bx bx-chevron-left"></i>
            </a>
            
            @for($i = 1; $i <= min(5, $lastPage); $i++)
              @php $displayPage = $i; @endphp
              @if($lastPage > 5 && $i == 4)
                <span class="px-2 text-muted">...</span>
                @php $displayPage = $lastPage; @endphp
              @endif
              @if($lastPage <= 5 || $i <= 3 || $i == 5)
                <a href="{{ route('camions.index', array_merge(request()->query(), ['page' => $displayPage])) }}" 
                   class="btn btn-sm {{ $page == $displayPage ? 'btn-primary rounded-circle' : 'btn-outline-secondary' }}" 
                   style="{{ $page == $displayPage ? 'width: 36px; height: 36px;' : '' }}">
                  {{ $displayPage }}
                </a>
              @endif
              @if($lastPage > 5 && $i == 4)
                @break
              @endif
            @endfor
            
            <a href="{{ route('camions.index', array_merge(request()->query(), ['page' => min($lastPage, $page + 1)])) }}" 
               class="btn btn-sm btn-outline-secondary {{ $page >= $lastPage ? 'disabled' : '' }}">
              <i class="bx bx-chevron-right"></i>
            </a>
            
            <span class="ms-3 text-muted">Go to</span>
            <form action="{{ route('camions.index') }}" method="GET" class="d-inline">
              @foreach(request()->query() as $key => $value)
                @if($key !== 'page')
                  <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
              @endforeach
              <input type="number" name="page" min="1" max="{{ $lastPage }}" value="{{ $page }}" 
                     class="form-control form-control-sm d-inline-block" style="width: 60px;">
            </form>
            <span class="text-muted">Page</span>
          </nav>
        </div>
      @endif
    @endif
  </div>
</div>
@endsection
