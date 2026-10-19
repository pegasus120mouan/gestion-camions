@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb" class="mb-2">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
        <li class="breadcrumb-item active">Rôles et accès</li>
      </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
      <h4 class="mb-0">Gestion des rôles</h4>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row g-4">
      @foreach($roles as $role)
        <div class="col-12 col-md-6 col-xl-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h5 class="mb-0 fw-bold">{{ $role['label'] }}</h5>
                  <small class="text-muted">{{ $role['key'] }}</small>
                </div>
                <span class="badge rounded-pill bg-primary">{{ $role['count'] }} / {{ $role['total'] }}</span>
              </div>

              <p class="text-body-secondary small flex-grow-1 mb-3">
                <strong>Modules :</strong> {{ $role['modules_resume'] }}
              </p>

              <a href="{{ route('acces.edit', $role['key']) }}" class="btn btn-outline-secondary w-100">
                <i class="bx bx-cog me-1"></i> Configurer les accès
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
