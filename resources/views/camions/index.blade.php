@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Camions</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauCamion">Nouveau camion</button>
    </div>

    <form method="GET" action="{{ route('camions.index') }}" class="mb-4">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Rechercher (immatriculation, marque, modèle)" value="{{ request('q') }}" />
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
                <th>Date</th>
                <th>Depenses</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @forelse($external_camions as $v)
                <tr>
                  <td>
                    <a href="{{ route('vehicules.depenses', ['vehicule_id' => $v['vehicules_id'] ?? 0, 'matricule' => $v['matricule_vehicule'] ?? '']) }}">
                      {{ $v['matricule_vehicule'] ?? '' }}
                    </a>
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
                    {{ $dateCreated }}
                  </td>
                  <td>
                    @php
                      $vehiculeId = $v['vehicules_id'] ?? 0;
                      $depenseTotal = \App\Models\Depense::where('vehicule_id', $vehiculeId)->sum('montant');
                    @endphp
                    {{ number_format($depenseTotal, 0, ',', ' ') }} FCFA
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center">Aucun camion</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        @else
          @php($disk = \Illuminate\Support\Facades\Storage::disk('s3'))
          <table class="table">
            <thead>
              <tr>
                <th>Image face</th>
                <th>Immatriculation</th>
                <th>Marque</th>
                <th>Modèle</th>
                <th>Année</th>
                <th>Chauffeur</th>
                <th>Actif</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @forelse($camions as $c)
                <tr>
                  <td>
                    @if(!empty($c->image_face))
                      <img src="{{ method_exists($disk, 'temporaryUrl') ? $disk->temporaryUrl($c->image_face, now()->addMinutes(60)) : $disk->url($c->image_face) }}" alt="Image face" style="width: 50px; height: 50px; object-fit: cover;" />
                    @endif
                  </td>
                  <td>
                    <a href="{{ route('camions.show', $c) }}">{{ $c->immatriculation }}</a>
                  </td>
                  <td>{{ $c->marque }}</td>
                  <td>{{ $c->modele }}</td>
                  <td>{{ $c->annee }}</td>
                  <td>{{ $c->chauffeur?->name }} {{ $c->chauffeur?->prenom }}</td>
                  <td>{{ $c->actif ? 'Oui' : 'Non' }}</td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('camions.edit', $c) }}">Modifier</a>
                    <form class="d-inline" method="POST" action="{{ route('camions.destroy', $c) }}" onsubmit="return confirm('Supprimer ce camion ?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center">Aucun camion</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        @endif
      </div>
    </div>

    <div class="mt-3">
      @if(!is_array($external_camions))
        {{ $camions->links() }}
      @endif
    </div>

    <div class="modal fade" id="modalNouveauCamion" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer un camion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('camions.store') }}" enctype="multipart/form-data">
              @csrf

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Immatriculation</label>
                  <input type="text" name="immatriculation" class="form-control" value="{{ old('immatriculation') }}" required />
                  @error('immatriculation')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Chauffeur</label>
                  <select name="chauffeur_id" class="form-select">
                    <option value="">-- Aucun --</option>
                    @foreach($chauffeurs as $ch)
                      <option value="{{ $ch->id }}" {{ (string) old('chauffeur_id') === (string) $ch->id ? 'selected' : '' }}>
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
                  <input type="text" name="marque" class="form-control" value="{{ old('marque') }}" />
                  @error('marque')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Modèle</label>
                  <input type="text" name="modele" class="form-control" value="{{ old('modele') }}" />
                  @error('modele')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Année</label>
                  <input type="number" name="annee" class="form-control" value="{{ old('annee') }}" min="1900" max="2100" />
                  @error('annee')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-center">
                  <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" {{ old('actif', '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="actif">Actif</label>
                  </div>
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
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    @if ($errors->any() || request()->boolean('create'))
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var el = document.getElementById('modalNouveauCamion');
          if (el && window.bootstrap) {
            new bootstrap.Modal(el).show();
          }
        });
      </script>
    @endif
  </div>
</div>
@endsection
