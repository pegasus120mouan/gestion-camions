@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des agents</h4>
    </div>

    <!-- Formulaire de recherche -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('agents.index') }}" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Rechercher</label>
            <input type="text" name="search" class="form-control" placeholder="Nom, prenom, numero..." value="{{ request('search') }}" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Chef d'equipe</label>
            <select name="id_chef" class="form-select">
              <option value="">Tous les chefs</option>
              @foreach($chefs ?? [] as $chef)
                <option value="{{ $chef['id_chef'] }}" {{ request('id_chef') == $chef['id_chef'] ? 'selected' : '' }}>
                  {{ $chef['nom_complet'] }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Rechercher</button>
            <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">Reinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Numero</th>
              <th>Nom</th>
              <th>Contact</th>
              <th>Chef d'equipe</th>
              <th>Date ajout</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($agents as $agent)
              <tr>
                <td><strong>{{ $agent['numero_agent'] ?? '' }}</strong></td>
                <td>
                  <a href="{{ route('agents.show', ['id_agent' => $agent['id_agent'] ?? 0]) }}" class="text-primary fw-bold text-decoration-none">
                    {{ $agent['nom_complet'] ?? '' }}
                  </a>
                </td>
                <td>{{ $agent['contact'] ?? '-' }}</td>
                <td>
                  @if(!empty($agent['chef_equipe']))
                    <span class="badge bg-label-primary">{{ $agent['chef_equipe']['nom_complet'] }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if(!empty($agent['date_ajout']))
                    {{ \Carbon\Carbon::parse($agent['date_ajout'])->format('d-m-Y') }}
                  @else
                    -
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center">Aucun agent</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(!empty($pagination) && $pagination['last_page'] > 1)
      <nav class="mt-4">
        <ul class="pagination justify-content-center">
          @php
            $currentPage = $pagination['current_page'];
            $lastPage = $pagination['last_page'];
            $queryString = request()->except('page');
          @endphp

          {{-- Previous --}}
          @if($currentPage > 1)
            <li class="page-item">
              <a class="page-link" href="{{ route('agents.index', array_merge($queryString, ['page' => $currentPage - 1])) }}">
                <i class="tf-icon bx bx-chevron-left"></i>
              </a>
            </li>
          @else
            <li class="page-item disabled">
              <span class="page-link"><i class="tf-icon bx bx-chevron-left"></i></span>
            </li>
          @endif

          {{-- Pages --}}
          @for($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++)
            <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
              <a class="page-link" href="{{ route('agents.index', array_merge($queryString, ['page' => $i])) }}">{{ $i }}</a>
            </li>
          @endfor

          {{-- Next --}}
          @if($currentPage < $lastPage)
            <li class="page-item">
              <a class="page-link" href="{{ route('agents.index', array_merge($queryString, ['page' => $currentPage + 1])) }}">
                <i class="tf-icon bx bx-chevron-right"></i>
              </a>
            </li>
          @else
            <li class="page-item disabled">
              <span class="page-link"><i class="tf-icon bx bx-chevron-right"></i></span>
            </li>
          @endif
        </ul>
      </nav>
    @endif

    @if(!empty($pagination))
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Resume</h5>
          <p><strong>Total agents:</strong> {{ $pagination['total'] ?? 0 }}</p>
          <p><strong>Page:</strong> {{ $pagination['current_page'] ?? 1 }} / {{ $pagination['last_page'] ?? 1 }}</p>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
