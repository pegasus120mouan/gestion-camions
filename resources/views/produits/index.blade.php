@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Produits</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauProduit">Nouveau produit</button>
    </div>

    <form method="GET" action="{{ route('produits.index') }}" class="mb-4">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Rechercher (nom)" value="{{ request('q') }}" />
        <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Nom</th>
              <th class="text-end">Tare</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($produits as $p)
              <tr>
                <td>{{ $p->nom }}</td>
                <td class="text-end">{{ $p->tare }}</td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('produits.edit', $p) }}">Modifier</a>
                  <form class="d-inline" method="POST" action="{{ route('produits.destroy', $p) }}" onsubmit="return confirm('Supprimer ce produit ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center">Aucun produit</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $produits->links() }}
    </div>

    <div class="modal fade" id="modalNouveauProduit" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer un produit</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('produits.store') }}">
              @csrf

              <div class="row">
                <div class="col-md-8 mb-3">
                  <label class="form-label">Nom</label>
                  <input type="text" name="nom" class="form-control" value="{{ old('nom') }}" required />
                  @error('nom')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Tare</label>
                  <input type="number" step="0.001" name="tare" class="form-control" value="{{ old('tare', 0) }}" required />
                  @error('tare')<div class="text-danger mt-1">{{ $message }}</div>@enderror
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
          var el = document.getElementById('modalNouveauProduit');
          if (el && window.bootstrap) {
            new bootstrap.Modal(el).show();
          }
        });
      </script>
    @endif
  </div>
</div>
@endsection
