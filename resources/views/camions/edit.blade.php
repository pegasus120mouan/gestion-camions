@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Modifier camion</h4>
      <a href="{{ route('camions.index') }}" class="btn btn-outline-secondary">Retour</a>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('camions.update', $camion) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Immatriculation</label>
              <input type="text" name="immatriculation" class="form-control" value="{{ old('immatriculation', $camion->immatriculation) }}" required />
              @error('immatriculation')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Chauffeur</label>
              <select name="chauffeur_id" class="form-select">
                <option value="">-- Aucun --</option>
                @foreach($chauffeurs as $ch)
                  <option value="{{ $ch->id }}" {{ (string) old('chauffeur_id', $camion->chauffeur_id) === (string) $ch->id ? 'selected' : '' }}>
                    {{ $ch->name }} {{ $ch->prenom }} ({{ $ch->login }})
                  </option>
                @endforeach
              </select>
              @error('chauffeur_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Marque</label>
              <input type="text" name="marque" class="form-control" value="{{ old('marque', $camion->marque) }}" />
              @error('marque')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Modèle</label>
              <input type="text" name="modele" class="form-control" value="{{ old('modele', $camion->modele) }}" />
              @error('modele')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Année</label>
              <input type="number" name="annee" class="form-control" value="{{ old('annee', $camion->annee) }}" min="1900" max="2100" />
              @error('annee')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-center">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" {{ old('actif', $camion->actif) ? 'checked' : '' }}>
                <label class="form-check-label" for="actif">Actif</label>
              </div>
              @error('actif')<div class="text-danger mt-4 ms-3">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Image face</label>
              <input type="file" name="image_face" class="form-control" accept="image/*" />
              @error('image_face')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Image profil gauche</label>
              <input type="file" name="image_profil_gauche" class="form-control" accept="image/*" />
              @error('image_profil_gauche')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Image profil droit</label>
              <input type="file" name="image_profil_droit" class="form-control" accept="image/*" />
              @error('image_profil_droit')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Image arrière</label>
              <input type="file" name="image_arriere" class="form-control" accept="image/*" />
              @error('image_arriere')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('camions.index') }}" class="btn btn-outline-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
