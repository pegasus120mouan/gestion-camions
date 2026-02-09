@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('agents.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux agents
        </a>
        <h4 class="mb-0">
          <i class="bx bx-user text-primary me-2"></i>
          {{ $agent['nom_complet'] ?? 'Agent' }}
        </h4>
        <small class="text-muted">N° Agent: {{ $agent['numero_agent'] ?? '-' }} | Contact: {{ $agent['contact'] ?? '-' }}</small>
      </div>
    </div>

    <div class="row">
      <!-- Informations de l'agent -->
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label text-muted">Numéro Agent</label>
              <p class="fw-bold mb-0">{{ $agent['numero_agent'] ?? '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Nom complet</label>
              <p class="fw-bold mb-0">{{ $agent['nom_complet'] ?? '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Contact</label>
              <p class="fw-bold mb-0">{{ $agent['contact'] ?? '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Chef d'équipe</label>
              <p class="fw-bold mb-0">
                @if(!empty($agent['chef_equipe']))
                  <span class="badge bg-label-primary">{{ $agent['chef_equipe']['nom_complet'] }}</span>
                @else
                  -
                @endif
              </p>
            </div>
            <div class="mb-0">
              <label class="form-label text-muted">Date d'ajout</label>
              <p class="fw-bold mb-0">
                @if(!empty($agent['date_ajout']))
                  {{ \Carbon\Carbon::parse($agent['date_ajout'])->format('d-m-Y') }}
                @else
                  -
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulaire de saisie -->
      <div class="col-md-8">
        <div class="card mb-4">
          <!-- Prix Agents Transporteur -->
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix Agents Transporteur</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrixTransporteur">
              <i class="bx bx-plus me-1"></i>Ajouter
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Usine</th>
                  <th class="text-end">Prix (FCFA)</th>
                  <th>Date début</th>
                  <th>Date fin</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($prixTransporteur ?? [] as $prix)
                  <tr>
                    <td><strong>{{ $prix->nom_usine }}</strong></td>
                    <td class="text-end">{{ number_format($prix->prix, 0, ',', ' ') }}</td>
                    <td>{{ $prix->date_debut ? $prix->date_debut->format('d-m-Y') : '-' }}</td>
                    <td>{{ $prix->date_fin ? $prix->date_fin->format('d-m-Y') : '-' }}</td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPrix{{ $prix->id }}">
                        <i class="bx bx-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('agents.prix.delete', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce prix ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="bx bx-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">Aucun prix configuré</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <!-- Prix Agents Transporteur PGF -->
        <div class="card mb-4">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix Agents Transporteur PGF</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrixPgf">
              <i class="bx bx-plus me-1"></i>Ajouter
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Usine</th>
                  <th class="text-end">Prix (FCFA)</th>
                  <th>Date début</th>
                  <th>Date fin</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($prixPgf ?? [] as $prix)
                  <tr>
                    <td><strong>{{ $prix->nom_usine }}</strong></td>
                    <td class="text-end">{{ number_format($prix->prix, 0, ',', ' ') }}</td>
                    <td>{{ $prix->date_debut ? $prix->date_debut->format('d-m-Y') : '-' }}</td>
                    <td>{{ $prix->date_fin ? $prix->date_fin->format('d-m-Y') : '-' }}</td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPrix{{ $prix->id }}">
                        <i class="bx bx-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('agents.prix.delete', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce prix ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="bx bx-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">Aucun prix configuré</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal Ajouter Prix Transporteur -->
<div class="modal fade" id="modalAddPrixTransporteur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.store', ['id_agent' => $agent['id_agent']]) }}">
        @csrf
        <input type="hidden" name="type" value="transporteur">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Ajouter Prix Transporteur</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Usine <span class="text-danger">*</span></label>
            <select name="id_usine" class="form-select" required onchange="this.form.nom_usine.value = this.options[this.selectedIndex].text">
              <option value="">Sélectionner une usine</option>
              @foreach($usines ?? [] as $usine)
                <option value="{{ $usine['id_usine'] }}">{{ $usine['nom_usine'] }}</option>
              @endforeach
            </select>
            <input type="hidden" name="nom_usine" value="">
          </div>
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" placeholder="Ex: 50">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control">
            </div>
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

<!-- Modal Ajouter Prix PGF -->
<div class="modal fade" id="modalAddPrixPgf" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.store', ['id_agent' => $agent['id_agent']]) }}">
        @csrf
        <input type="hidden" name="type" value="pgf">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title text-white">Ajouter Prix PGF</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Usine <span class="text-danger">*</span></label>
            <select name="id_usine" class="form-select" required onchange="this.form.nom_usine.value = this.options[this.selectedIndex].text">
              <option value="">Sélectionner une usine</option>
              @foreach($usines ?? [] as $usine)
                <option value="{{ $usine['id_usine'] }}">{{ $usine['nom_usine'] }}</option>
              @endforeach
            </select>
            <input type="hidden" name="nom_usine" value="">
          </div>
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" placeholder="Ex: 30">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals pour modifier les prix -->
@foreach($prixTransporteur ?? [] as $prix)
<div class="modal fade" id="modalEditPrix{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.update', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Modifier Prix - {{ $prix->nom_usine }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" value="{{ $prix->prix }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ $prix->date_debut ? $prix->date_debut->format('Y-m-d') : '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}">
            </div>
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
@endforeach

@foreach($prixPgf ?? [] as $prix)
<div class="modal fade" id="modalEditPrix{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.update', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title text-white">Modifier Prix PGF - {{ $prix->nom_usine }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" value="{{ $prix->prix }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ $prix->date_debut ? $prix->date_debut->format('Y-m-d') : '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach
@endsection
