@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Commis de pont</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="bx bx-plus me-1"></i> Ajouter un commis
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('commis.index') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Pont</label>
            <select name="id_pont" class="form-select">
              <option value="">Tous les ponts</option>
              @foreach($ponts as $pont)
                <option value="{{ $pont['id_pont'] ?? '' }}" @selected(request('id_pont') == ($pont['id_pont'] ?? ''))>
                  {{ $pont['nom_pont'] ?? '' }} ({{ $pont['code_pont'] ?? '' }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nom, prénom, contact, pont..." />
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('commis.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>Nom</th>
              <th>Prénom</th>
              <th>Contact</th>
              <th>Pont</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($commis as $c)
              <tr>
                <td>
                  <img src="{{ $c->avatar_url }}" alt="Avatar" class="rounded-circle" width="40" height="40" style="object-fit: cover;" />
                </td>
                <td><strong>{{ $c->nom }}</strong></td>
                <td>{{ $c->prenom }}</td>
                <td>{{ $c->contact ?? '-' }}</td>
                <td>
                  <span class="fw-medium">{{ $c->nom_pont ?? '-' }}</span>
                  @if($c->code_pont)
                    <br><small class="text-muted">{{ $c->code_pont }}</small>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $c->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDelete{{ $c->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">Aucun commis enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $commis->links() }}
    </div>
  </div>
</div>

<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ajouter un commis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('commis.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          @include('commis._form', ['commi' => null])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach($commis as $c)
<div class="modal fade" id="modalEdit{{ $c->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier le commis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('commis.update', $c) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @include('commis._form', ['commi' => $c, 'edit' => true])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDelete{{ $c->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Supprimer le commis <strong>{{ $c->nom_complet }}</strong> du pont <strong>{{ $c->nom_pont }}</strong> ?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('commis.destroy', $c) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

<script>
function togglePassword(inputId, iconId) {
  var input = document.getElementById(inputId);
  var icon = document.getElementById(iconId);
  if (!input || !icon) return;
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('bx-hide');
    icon.classList.add('bx-show');
  } else {
    input.type = 'password';
    icon.classList.remove('bx-show');
    icon.classList.add('bx-hide');
  }
}
</script>
@endsection
