@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des Usines</h4>
    </div>

    <!-- Formulaire de recherche -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('usines.index') }}" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Rechercher</label>
            <input type="text" name="search" class="form-control" placeholder="Nom usine..." value="{{ request('search') }}" />
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Rechercher</button>
            <a href="{{ route('usines.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table table-hover">
          <thead>
            <tr style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
              <th class="text-white">Nom Usine</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($usines as $u)
              <tr>
                <td><strong>{{ $u['nom_usine'] ?? '-' }}</strong></td>
              </tr>
            @empty
              <tr>
                <td class="text-center py-4">Aucune usine trouvée</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(!empty($pagination))
      @php
        $currentPage = (int) ($pagination['current_page'] ?? 1);
        $lastPage = (int) ($pagination['last_page'] ?? 1);
        $total = (int) ($pagination['total'] ?? 0);
      @endphp
      @if($lastPage > 1)
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
              <a class="page-link" href="{{ route('usines.index', ['page' => $currentPage - 1, 'search' => request('search')]) }}">Précédent</a>
            </li>
            @for($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++)
              <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route('usines.index', ['page' => $i, 'search' => request('search')]) }}">{{ $i }}</a>
              </li>
            @endfor
            <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
              <a class="page-link" href="{{ route('usines.index', ['page' => $currentPage + 1, 'search' => request('search')]) }}">Suivant</a>
            </li>
          </ul>
          <p class="text-center text-muted">Page {{ $currentPage }} sur {{ $lastPage }} ({{ $total }} usines)</p>
        </nav>
      @endif
    @endif

    @if(count($usines) > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Résumé</h5>
          <p><strong>Total usines:</strong> {{ count($usines) }}</p>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
