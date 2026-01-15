@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Modifier un produit</h4>
      <a href="{{ route('produits.index') }}" class="btn btn-outline-secondary">Retour</a>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('produits.update', $produit) }}">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">Nom</label>
              <input type="text" name="nom" class="form-control" value="{{ old('nom', $produit->nom) }}" required />
              @error('nom')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Tare</label>
              <input type="number" step="0.001" name="tare" class="form-control" value="{{ old('tare', $produit->tare) }}" required />
              @error('tare')<div class="text-danger mt-1">{{ $message }}</div>@enderror
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
