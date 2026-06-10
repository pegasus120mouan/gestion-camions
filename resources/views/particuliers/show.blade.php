@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('particuliers.index') }}" class="text-primary mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i>Retour aux groupes particuliers
        </a>
        <h4 class="mb-0"><i class="bx bx-group text-primary me-2"></i>{{ $groupe->nom_groupe }}</h4>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('particuliers.agents.index', ['particulier_groupe_id' => $groupe->id]) }}" class="btn btn-outline-primary">
          <i class="bx bx-list-ul me-1"></i>Tous les agents
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddAgent">
          <i class="bx bx-plus me-1"></i>Ajouter un agent
        </button>
      </div>
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

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-1">Total agents</h6>
                <h3 class="mb-0">{{ $groupe->agents->count() }}</h3>
              </div>
              <i class="bx bx-group" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Agents du groupe</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>N° agent</th>
              <th>Nom complet</th>
              <th>Contact</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($groupe->agents as $agent)
              @php
                $info = $agentsById[(int)($agent->id_agent ?? 0)] ?? null;
                $nom = $info ? ($info['nom_complet'] ?? $agent->nom) : $agent->nom;
                $numero = $info ? ($info['numero_agent'] ?? $agent->numero_agent) : $agent->numero_agent;
                $contact = $info ? ($info['contact'] ?? $agent->contact ?? '-') : ($agent->contact ?? '-');
              @endphp
              <tr>
                <td><code>{{ $numero }}</code></td>
                <td><strong>{{ $nom }}</strong></td>
                <td>{{ $contact }}</td>
                <td class="text-center">
                  <form method="POST" action="{{ route('particuliers.agents.destroy', $agent) }}" class="d-inline" onsubmit="return confirm('Supprimer cet agent ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucun agent dans ce groupe</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAddAgent" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-user-plus me-2"></i>Ajouter un agent</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('particuliers.agents.store') }}" id="formAddAgentApi">
        @csrf
        <input type="hidden" name="particulier_groupe_id" value="{{ $groupe->id }}" />
        <input type="hidden" name="id_agent" id="hidden_id_agent" value="" />
        <input type="hidden" name="numero_api" id="hidden_numero_api" value="" />
        <input type="hidden" name="nom_api" id="hidden_nom_api" value="" />
        <input type="hidden" name="prenoms_api" id="hidden_prenoms_api" value="" />
        <input type="hidden" name="contact_api" id="hidden_contact_api" value="" />
        <div class="modal-body">
          @if(count($agentsDisponibles ?? []) > 0)
            <div class="mb-3">
              <label class="form-label">Sélectionner un agent <span class="text-danger">*</span></label>
              <select class="form-select" id="selectAgentApi" required>
                <option value="">-- Choisir un agent --</option>
                @foreach($agentsDisponibles as $a)
                  <option value="{{ $a['id_agent'] }}"
                    data-numero="{{ $a['numero_agent'] ?? '' }}"
                    data-nom="{{ $a['nom_complet'] ?? '' }}"
                    data-prenoms=""
                    data-contact="{{ $a['contact'] ?? '' }}">
                    {{ $a['numero_agent'] ?? '' }} – {{ $a['nom_complet'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
          @else
            <p class="text-muted mb-0">Tous les agents de l’API sont déjà dans ce groupe.</p>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary" @if(empty($agentsDisponibles)) disabled @endif>
            <i class="bx bx-check me-1"></i>Ajouter
          </button>
        </div>
      </form>
      <script>
      document.getElementById('selectAgentApi')?.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        document.getElementById('hidden_id_agent').value    = opt.value;
        document.getElementById('hidden_numero_api').value  = opt.dataset.numero  || '';
        document.getElementById('hidden_nom_api').value     = opt.dataset.nom      || '';
        document.getElementById('hidden_prenoms_api').value = opt.dataset.prenoms  || '';
        document.getElementById('hidden_contact_api').value = opt.dataset.contact  || '';
      });
      </script>
    </div>
  </div>
</div>
@endsection
