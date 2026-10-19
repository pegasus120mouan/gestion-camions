@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Gestionnaires</h4>
        <small class="text-muted">
          Comme PGF : pas rattaché à un pont. Connexion sur le même écran de login, avec le même accès plateforme (périmètre chef PGF).
        </small>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelGestionnaire">
        <i class="bx bx-plus me-1"></i> Nouveau gestionnaire
      </button>
    </div>

    <form method="GET" action="{{ route('utilisateurs.gestionnaires') }}" class="mb-4">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Rechercher (nom, prénom, login, contact)" value="{{ request('q') }}" />
        <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
      </div>
    </form>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if (session('code_pin_clair'))
      <div class="alert alert-info">
        Code PIN généré : <strong>{{ session('code_pin_clair') }}</strong>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>Nom</th>
              <th>Prénom</th>
              <th>Login</th>
              <th>Contact</th>
              <th>Rôle</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($utilisateurs as $u)
              <tr>
                <td>
                  <div class="avatar avatar-online">
                    <img src="{{ $u->avatar_url }}" alt class="w-px-40 h-auto rounded-circle" />
                  </div>
                </td>
                <td>{{ $u->name }}</td>
                <td>{{ $u->prenom }}</td>
                <td>{{ $u->login }}</td>
                <td>{{ $u->contact ?? '-' }}</td>
                <td><span class="badge bg-label-primary">gestionnaire</span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('utilisateurs.edit', $u) }}">Modifier</a>
                  <form class="d-inline" method="POST" action="{{ route('utilisateurs.destroy', $u) }}" onsubmit="return confirm('Supprimer ce gestionnaire ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center">Aucun gestionnaire</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $utilisateurs->links() }}
    </div>

    <div class="modal fade" id="modalNouvelGestionnaire" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer un gestionnaire</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info py-2">
              Ce compte se connecte comme PGF (même écran de login) et gère la plateforme sans être lié à un pont.
            </div>
            <form method="POST" action="{{ route('utilisateurs.store') }}" enctype="multipart/form-data">
              @csrf
              <input type="hidden" name="role" value="gestionnaire" />

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nom <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" value="{{ old('name') }}" required />
                  @error('name')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Prénom</label>
                  <input type="text" name="prenom" class="form-control" value="{{ old('prenom') }}" />
                  @error('prenom')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Login <span class="text-danger">*</span></label>
                  <input type="text" name="login" class="form-control" value="{{ old('login') }}" required />
                  @error('login')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Contact</label>
                  <input type="text" name="contact" class="form-control" value="{{ old('contact') }}" />
                  @error('contact')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Avatar</label>
                  <input type="file" name="avatar" class="form-control" accept="image/*" />
                  @error('avatar')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                  <input type="password" name="password" class="form-control" required />
                  @error('password')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Confirmer mot de passe <span class="text-danger">*</span></label>
                  <input type="password" name="password_confirmation" class="form-control" required />
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
          var el = document.getElementById('modalNouvelGestionnaire');
          if (el && window.bootstrap) {
            new bootstrap.Modal(el).show();
          }
        });
      </script>
    @endif
  </div>
</div>
@endsection
