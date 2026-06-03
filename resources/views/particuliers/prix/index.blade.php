@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Gestion Particuliers /</span> Prix unitaire
    </h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('particuliers.prix.index') }}" class="row g-3 align-items-end">
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
            <label class="form-label">Recherche par nom</label>
            <input type="text" name="q" class="form-control" value="{{ $search ?? request('q') }}" placeholder="N°, nom, prénoms, contact..." list="prix_agents_noms_list" autocomplete="off" />
            <datalist id="prix_agents_noms_list">
              @foreach($agentNoms ?? [] as $nomAgent)
                <option value="{{ $nomAgent }}"></option>
              @endforeach
            </datalist>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('particuliers.prix.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-money me-2"></i>Liste des agents</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>N° agent</th>
              <th>Agent</th>
              <th>Groupe</th>
              <th>Contact</th>
              <th class="text-center">Prix configurés</th>
            </tr>
          </thead>
          <tbody>
            @forelse($agents as $agent)
              <tr>
                <td><code>{{ $agent->numero_agent }}</code></td>
                <td>
                  <a href="{{ route('particuliers.prix.show', $agent) }}" class="text-primary fw-bold">
                    {{ $agent->nom_complet }}
                  </a>
                </td>
                <td>{{ $agent->groupe?->nom_groupe ?? '-' }}</td>
                <td>{{ $agent->contact ?? '-' }}</td>
                <td class="text-center">
                  <span class="badge bg-primary">{{ $agent->prix_count }}</span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  @if(!empty($search))
                    Aucun agent trouvé pour « {{ $search }} »
                  @else
                    Aucun agent enregistré
                  @endif
                </td>
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
@endsection
