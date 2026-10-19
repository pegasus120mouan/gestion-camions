@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Produits</h4>
      @if(!empty($peutCreerProduit))
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauProduit">Nouveau produit</button>
      @else
        <button type="button" class="btn btn-primary" disabled title="Accès non autorisé">Nouveau produit</button>
      @endif
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

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
                <td>
                  <a href="{{ route('produits.show', $p) }}" class="text-primary fw-bold text-decoration-none">
                    {{ $p->nom }}
                  </a>
                </td>
                <td class="text-end">{{ $p->tare }}</td>
                <td class="text-end">
                  @if(!empty($peutModifierProduit))
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary btn-edit-produit"
                      data-bs-toggle="modal"
                      data-bs-target="#modalEditProduit"
                      data-id="{{ $p->id }}"
                      data-nom="{{ $p->nom }}"
                      data-tare="{{ $p->tare }}"
                      data-action="{{ route('produits.update', $p) }}"
                    >Modifier</button>
                  @else
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Accès non autorisé">Modifier</button>
                  @endif

                  @if(!empty($peutSupprimerProduit))
                    <form class="d-inline" method="POST" action="{{ route('produits.destroy', $p) }}" onsubmit="return confirm('Supprimer le produit « {{ $p->nom }} » ?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                    </form>
                  @else
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Accès non autorisé">Supprimer</button>
                  @endif
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
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer un produit</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ route('produits.store') }}">
            @csrf
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control" value="{{ old('nom') }}" required />
                @error('nom')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="mb-3">
                <label class="form-label">Tare <span class="text-danger">*</span></label>
                <input type="number" step="0.001" name="tare" class="form-control" value="{{ old('tare', 0) }}" required />
                @error('tare')<div class="text-danger mt-1">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalEditProduit" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modifier le produit</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="formEditProduit" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="nom" id="edit_produit_nom" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Tare <span class="text-danger">*</span></label>
                <input type="number" step="0.001" name="tare" id="edit_produit_tare" class="form-control" required />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var modalEdit = document.getElementById('modalEditProduit');
  if (modalEdit) {
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var btn = event.relatedTarget;
      if (!btn) return;
      var form = document.getElementById('formEditProduit');
      form.action = btn.getAttribute('data-action') || '';
      document.getElementById('edit_produit_nom').value = btn.getAttribute('data-nom') || '';
      document.getElementById('edit_produit_tare').value = btn.getAttribute('data-tare') || '0';
    });
  }

  @if ($errors->any() && old('_method') === 'PUT')
    var el = document.getElementById('modalEditProduit');
    if (el && window.bootstrap) {
      new bootstrap.Modal(el).show();
    }
  @elseif ($errors->any() || request()->boolean('create'))
    var elCreate = document.getElementById('modalNouveauProduit');
    if (elCreate && window.bootstrap) {
      new bootstrap.Modal(elCreate).show();
    }
  @endif
});
</script>
@endsection
