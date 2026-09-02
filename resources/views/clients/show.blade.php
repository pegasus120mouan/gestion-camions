@extends('layout.main')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <a href="{{ route('clients.index', ['tab' => 'particuliers']) }}" class="text-primary mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i>Retour aux particuliers
        </a>
        <h4 class="mb-1">
          <i class="bx bx-user text-primary me-2"></i>{{ $client->nom_complet }}
        </h4>
        <p class="text-muted mb-0">
          Code :
          <code class="text-primary">{{ $client->code }}</code>
        </p>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditParticulier">
          <i class="bx bx-edit me-1"></i>Modifier
        </button>
        <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Supprimer ce particulier ?')">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger">
            <i class="bx bx-trash me-1"></i>Supprimer
          </button>
        </form>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any() && old('_site_action') !== 'create')
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
        <div class="row g-4">
          <div class="col-md-3">
            <small class="text-muted d-block">Code</small>
            <strong><code>{{ $client->code }}</code></strong>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Nom</small>
            <strong>{{ $client->nom }}</strong>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Prénoms</small>
            <strong>{{ $client->prenoms ?: '-' }}</strong>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Contact</small>
            <strong>{{ $client->contact ?: '-' }}</strong>
          </div>
          <div class="col-md-9">
            <small class="text-muted d-block">Adresse</small>
            <strong>{{ $client->adresse ?: '-' }}</strong>
          </div>
          <div class="col-md-3">
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
          'ownerType' => 'particulier',
          'ownerId' => $client->id,
        ])
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditParticulier" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-white"><i class="bx bx-edit me-2"></i>Modifier le particulier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" class="form-control" value="{{ $client->code }}" readonly />
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nom <span class="text-danger">*</span></label>
              <input type="text" name="nom" class="form-control" required maxlength="100" value="{{ old('nom', $client->nom) }}" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Prénoms</label>
              <input type="text" name="prenoms" class="form-control" maxlength="150" value="{{ old('prenoms', $client->prenoms) }}" />
            </div>
            <div class="col-md-12">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" class="form-control" maxlength="50" value="{{ old('contact', $client->contact) }}" />
            </div>
            <div class="col-md-12">
              <label class="form-label">Adresse</label>
              <textarea name="adresse" class="form-control" rows="2" maxlength="500">{{ old('adresse', $client->adresse) }}</textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning text-white"><i class="bx bx-check me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($errors->any() && old('_site_action') !== 'create' && old('owner_type') === null)
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var modal = document.getElementById('modalEditParticulier');
      if (modal && window.bootstrap) {
        new bootstrap.Modal(modal).show();
      }
    });
  </script>
@endif
@endsection
