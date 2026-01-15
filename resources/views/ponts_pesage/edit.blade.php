@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Modifier un pont de pesage</h4>
      <a href="{{ route('ponts_pesage.index') }}" class="btn btn-outline-secondary">Retour</a>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('ponts_pesage.update', $pontPesage) }}">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Code</label>
              <input type="text" name="code" class="form-control" value="{{ old('code', $pontPesage->code) }}" required />
              @error('code')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label">Nom</label>
              <input type="text" name="nom" class="form-control" value="{{ old('nom', $pontPesage->nom) }}" required />
              @error('nom')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">Localisation</label>
              <input type="text" name="localisation" class="form-control" value="{{ old('localisation', $pontPesage->localisation) }}" />
              @error('localisation')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-center">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" {{ old('actif', $pontPesage->actif) ? 'checked' : '' }}>
                <label class="form-check-label" for="actif">Actif</label>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
