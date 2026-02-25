@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Ajouter un chef des chargeurs</h4>
      <a href="{{ route('chef_chargeurs.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Retour
      </a>
    </div>

    <div class="card">
      <div class="card-body">
        <form action="{{ route('chef_chargeurs.store') }}" method="POST">
          @csrf
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nom <span class="text-danger">*</span></label>
              <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror" value="{{ old('nom') }}" required />
              @error('nom')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Prénoms <span class="text-danger">*</span></label>
              <input type="text" name="prenoms" class="form-control @error('prenoms') is-invalid @enderror" value="{{ old('prenoms') }}" required />
              @error('prenoms')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" class="form-control @error('contact') is-invalid @enderror" value="{{ old('contact') }}" placeholder="Numéro de téléphone" />
              @error('contact')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('chef_chargeurs.index') }}" class="btn btn-outline-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-1"></i> Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
