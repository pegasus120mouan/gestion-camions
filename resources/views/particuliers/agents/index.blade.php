@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-user text-primary me-2"></i>Liste des agents particuliers</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="bx bx-plus me-1"></i> Ajouter un agent
      </button>
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

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('particuliers.agents.index') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Groupe</label>
            <select name="particulier_groupe_id" class="form-select">
              <option value="">Tous les groupes</option>
              @foreach($groupes as $groupe)
                <option value="{{ $groupe->id }}" @selected(request('particulier_groupe_id') == $groupe->id)>
                  {{ $groupe->nom_groupe }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="N°, nom, prénoms, contact, groupe..." />
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('particuliers.agents.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>N° agent</th>
              <th>Nom</th>
              <th>Prénoms</th>
              <th>Contact</th>
              <th>Groupe</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($agents as $agent)
              <tr>
                <td><code>{{ $agent->numero_agent }}</code></td>
                <td><strong>{{ $agent->nom }}</strong></td>
                <td>{{ $agent->prenoms }}</td>
                <td>{{ $agent->contact ?? '-' }}</td>
                <td>
                  @if($agent->groupe)
                    <a href="{{ route('particuliers.show', $agent->groupe->id) }}" class="text-primary">
                      {{ $agent->groupe->nom_groupe }}
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $agent->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDelete{{ $agent->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">Aucun agent enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $agents->links() }}
    </div>
  </div>
</div>

<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ajouter un agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('particuliers.agents.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          @include('particuliers.agents._form', ['agent' => null, 'groupes' => $groupes, 'prochainNumero' => $prochainNumero ?? null])
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

@foreach($agents as $agent)
<div class="modal fade" id="modalEdit{{ $agent->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier l'agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('particuliers.agents.update', $agent) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @include('particuliers.agents._form', ['agent' => $agent, 'groupes' => $groupes, 'edit' => true])
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

<div class="modal fade" id="modalDelete{{ $agent->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Supprimer l'agent <strong>{{ $agent->numero_agent }} — {{ $agent->nom_complet }}</strong>
        @if($agent->groupe)
          du groupe <strong>{{ $agent->groupe->nom_groupe }}</strong> ?
        @else
          ?
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('particuliers.agents.destroy', $agent) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach
@endsection
