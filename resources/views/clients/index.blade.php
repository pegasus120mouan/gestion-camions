@extends('layout.main')

@section('content')
<style>
  .clients-page .clients-tabs {
    display: inline-flex;
    gap: 0.35rem;
    padding: 0.35rem;
    background: #f2f3f8;
    border-radius: 0.55rem;
  }
  .clients-page .clients-tabs .nav-link {
    border: 0;
    border-radius: 0.4rem;
    color: #6f6b7d;
    font-weight: 500;
    padding: 0.55rem 1.1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }
  .clients-page .clients-tabs .nav-link:hover {
    color: #7367f0;
    background: rgba(115, 103, 240, 0.08);
  }
  .clients-page .clients-tabs .nav-link.active {
    color: #fff;
    background: #7367f0;
    box-shadow: 0 2px 6px rgba(115, 103, 240, 0.35);
  }
  .clients-page .clients-search {
    background: #f8f8fa;
    border: 1px solid #ebebed;
    border-radius: 0.5rem;
    padding: 0.85rem 1rem;
  }
  .clients-page .clients-empty {
    padding: 2.5rem 1rem;
    text-align: center;
    color: #a8a6b3;
  }
  .clients-page .clients-empty i {
    font-size: 2.25rem;
    display: block;
    margin-bottom: 0.65rem;
    opacity: 0.7;
  }
  .clients-page .table thead th {
    text-transform: none;
    letter-spacing: 0;
    font-size: 0.8125rem;
    color: #5d596c;
    background: #f8f8fa;
    border-bottom-width: 1px;
  }
</style>

<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y clients-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <h4 class="mb-1">Liste des clients</h4>
        <p class="text-muted mb-0">Usines et particuliers pour les transferts</p>
      </div>
      @if($tab === 'particuliers')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddParticulier">
          <i class="bx bx-plus me-1"></i> Ajouter un particulier
        </button>
      @endif
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
          <nav class="clients-tabs" aria-label="Type de client">
            <a
              class="nav-link {{ $tab === 'usines' ? 'active' : '' }}"
              href="{{ route('clients.index', ['tab' => 'usines'] + (request('search') ? ['search' => request('search')] : [])) }}"
            >
              <i class="bx bx-buildings"></i> Usines
            </a>
            <a
              class="nav-link {{ $tab === 'particuliers' ? 'active' : '' }}"
              href="{{ route('clients.index', ['tab' => 'particuliers'] + (request('search') ? ['search' => request('search')] : [])) }}"
            >
              <i class="bx bx-user"></i> Particuliers
            </a>
          </nav>
        </div>

        @if($tab === 'usines')
          <form method="GET" action="{{ route('clients.index') }}" class="clients-search mb-4">
            <input type="hidden" name="tab" value="usines" />
            <div class="row g-2 align-items-center">
              <div class="col-md-7 col-lg-6">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                  <input
                    type="text"
                    name="search"
                    class="form-control"
                    value="{{ $search }}"
                    placeholder="Rechercher une usine..."
                    aria-label="Rechercher une usine"
                  />
                </div>
              </div>
              <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">Rechercher</button>
                <a href="{{ route('clients.index', ['tab' => 'usines']) }}" class="btn btn-outline-secondary">Réinit.</a>
              </div>
            </div>
          </form>

          @if(!empty($usinesError))
            <div class="alert alert-warning mb-3">{{ $usinesError }}</div>
          @endif

          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Nom usine</th>
                </tr>
              </thead>
              <tbody>
                @forelse($usines as $usine)
                  <tr>
                    <td>
                      @if(!empty($usine['id_usine']))
                        <a href="{{ route('clients.usines.show', $usine['id_usine']) }}" class="fw-semibold text-primary">
                          {{ $usine['nom_usine'] ?? '-' }}
                        </a>
                      @else
                        <strong>{{ $usine['nom_usine'] ?? '-' }}</strong>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td>
                      <div class="clients-empty">
                        <i class="bx bx-buildings"></i>
                        Aucune usine trouvée
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        @else
          <form method="GET" action="{{ route('clients.index') }}" class="clients-search mb-4">
            <input type="hidden" name="tab" value="particuliers" />
            <div class="row g-2 align-items-center">
              <div class="col-md-7 col-lg-6">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                  <input
                    type="text"
                    name="search"
                    class="form-control"
                    value="{{ $search }}"
                    placeholder="Rechercher (code, nom, prénoms, contact)..."
                    aria-label="Rechercher un particulier"
                  />
                </div>
              </div>
              <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">Rechercher</button>
                <a href="{{ route('clients.index', ['tab' => 'particuliers']) }}" class="btn btn-outline-secondary">Réinit.</a>
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Nom</th>
                  <th>Prénoms</th>
                  <th>Contact</th>
                  <th>Adresse</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($particuliers as $particulier)
                  <tr>
                    <td>
                      <a href="{{ route('clients.show', $particulier) }}" class="fw-semibold text-primary">
                        <code>{{ $particulier->code }}</code>
                      </a>
                    </td>
                    <td><strong>{{ $particulier->nom }}</strong></td>
                    <td>{{ $particulier->prenoms ?: '-' }}</td>
                    <td>{{ $particulier->contact ?: '-' }}</td>
                    <td>{{ $particulier->adresse ?: '-' }}</td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <a
                          href="{{ route('clients.show', $particulier) }}"
                          class="btn btn-sm btn-icon btn-outline-primary"
                          title="Voir"
                        >
                          <i class="bx bx-show"></i>
                        </a>
                        <button
                          type="button"
                          class="btn btn-sm btn-icon btn-outline-warning"
                          data-bs-toggle="modal"
                          data-bs-target="#modalEditParticulier{{ $particulier->id }}"
                          title="Modifier"
                        >
                          <i class="bx bx-edit"></i>
                        </button>
                        <form
                          method="POST"
                          action="{{ route('clients.destroy', $particulier) }}"
                          class="d-inline"
                          onsubmit="return confirm('Supprimer ce particulier ?')"
                        >
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Supprimer">
                            <i class="bx bx-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6">
                      <div class="clients-empty">
                        <i class="bx bx-user"></i>
                        Aucun particulier enregistré
                        <div class="mt-3">
                          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddParticulier">
                            <i class="bx bx-plus me-1"></i> Ajouter un particulier
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if($particuliers && $particuliers->hasPages())
            <div class="mt-3">
              {{ $particuliers->links() }}
            </div>
          @endif
        @endif
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAddParticulier" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="bx bx-user-plus me-2"></i>Ajouter un particulier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('clients.store') }}">
        @csrf
        <div class="modal-body">
          <div class="alert alert-light border mb-3 py-2 small">
            Un code sera généré automatiquement (ex. <code>CLI-0001</code>).
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nom <span class="text-danger">*</span></label>
              <input type="text" name="nom" class="form-control" required maxlength="100" value="{{ old('nom') }}" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Prénoms</label>
              <input type="text" name="prenoms" class="form-control" maxlength="150" value="{{ old('prenoms') }}" />
            </div>
            <div class="col-md-12">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" class="form-control" maxlength="50" value="{{ old('contact') }}" placeholder="Téléphone" />
            </div>
            <div class="col-md-12">
              <label class="form-label">Adresse</label>
              <textarea name="adresse" class="form-control" rows="2" maxlength="500">{{ old('adresse') }}</textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($tab === 'particuliers' && $particuliers)
  @foreach($particuliers as $particulier)
    <div class="modal fade" id="modalEditParticulier{{ $particulier->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title text-white"><i class="bx bx-edit me-2"></i>Modifier le particulier</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="{{ route('clients.update', $particulier) }}">
            @csrf
            @method('PUT')
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" class="form-control" value="{{ $particulier->code }}" readonly />
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nom <span class="text-danger">*</span></label>
                  <input type="text" name="nom" class="form-control" required maxlength="100" value="{{ old('nom', $particulier->nom) }}" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Prénoms</label>
                  <input type="text" name="prenoms" class="form-control" maxlength="150" value="{{ old('prenoms', $particulier->prenoms) }}" />
                </div>
                <div class="col-md-12">
                  <label class="form-label">Contact</label>
                  <input type="text" name="contact" class="form-control" maxlength="50" value="{{ old('contact', $particulier->contact) }}" />
                </div>
                <div class="col-md-12">
                  <label class="form-label">Adresse</label>
                  <textarea name="adresse" class="form-control" rows="2" maxlength="500">{{ old('adresse', $particulier->adresse) }}</textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-warning text-white"><i class="bx bx-check me-1"></i>Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach
@endif

@if($errors->any() && old('nom') !== null && !request()->routeIs('clients.update'))
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var modal = document.getElementById('modalAddParticulier');
      if (modal && window.bootstrap) {
        new bootstrap.Modal(modal).show();
      }
    });
  </script>
@endif
@endsection
