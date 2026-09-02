@extends('layout.main')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
      <a href="{{ route('clients.index', ['tab' => 'usines']) }}" class="text-primary mb-2 d-inline-block">
        <i class="bx bx-arrow-back me-1"></i>Retour aux usines
      </a>
      <h4 class="mb-0"><i class="bx bx-buildings text-primary me-2"></i>{{ $usine['nom_usine'] }}</h4>
      <p class="text-muted mb-0">Fiche client — usine</p>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <small class="text-muted d-block">Nom de l'usine</small>
            <strong>{{ $usine['nom_usine'] }}</strong>
          </div>
          <div class="col-md-6">
            <small class="text-muted d-block">Nombre de sites</small>
            <strong>{{ $sites->count() }}</strong>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        @include('clients._sites', [
          'sites' => $sites,
          'ownerType' => 'usine',
          'ownerId' => $usine['id_usine'],
        ])
      </div>
    </div>
  </div>
</div>
@endsection
