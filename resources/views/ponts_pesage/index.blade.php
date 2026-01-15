@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Ponts de pesage</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauPont">Nouveau pont</button>
    </div>

    <form method="GET" action="{{ route('ponts_pesage.index') }}" class="mb-4">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Rechercher (code, nom, localisation)" value="{{ request('q') }}" />
        <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Localisation</th>
              <th>Actif</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($ponts as $p)
              <tr>
                <td>{{ $p->code }}</td>
                <td>{{ $p->nom }}</td>
                <td>{{ $p->localisation }}</td>
                <td>{{ $p->actif ? 'Oui' : 'Non' }}</td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('ponts_pesage.edit', $p) }}">Modifier</a>
                  <form class="d-inline" method="POST" action="{{ route('ponts_pesage.destroy', $p) }}" onsubmit="return confirm('Supprimer ce pont de pesage ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center">Aucun pont</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $ponts->links() }}
    </div>

    <div class="modal fade" id="modalNouveauPont" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer un pont de pesage</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('ponts_pesage.store') }}">
              @csrf

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Code</label>
                  <input type="text" name="code" class="form-control" value="{{ old('code') }}" required />
                  @error('code')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8 mb-3">
                  <label class="form-label">Nom</label>
                  <input type="text" name="nom" class="form-control" value="{{ old('nom') }}" required />
                  @error('nom')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row">
                <div class="col-md-8 mb-3">
                  <label class="form-label">Localisation</label>
                  <input type="text" name="localisation" class="form-control" value="{{ old('localisation') }}" />
                  @error('localisation')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-center">
                  <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" {{ old('actif', '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="actif">Actif</label>
                  </div>
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
          var el = document.getElementById('modalNouveauPont');
          if (el && window.bootstrap) {
            new bootstrap.Modal(el).show();
          }
        });
      </script>
    @endif
  </div>
</div>
@endsection
