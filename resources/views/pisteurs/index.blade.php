@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-leaf text-primary me-2"></i>Pisteurs</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="bx bx-plus me-1"></i> Ajouter
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
        @foreach($errors->all() as $error)
          {{ $error }}<br>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Prénoms</th>
              <th>Contact</th>
              <th>Prix unitaire</th>
              <th>Période</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($pisteurs as $pisteur)
              <tr>
                <td>
                  <a href="{{ route('pisteurs.show', $pisteur) }}" class="text-primary">
                    <strong>{{ $pisteur->nom }}</strong>
                  </a>
                </td>
                <td>{{ $pisteur->prenoms }}</td>
                <td>{{ $pisteur->contact ?? '-' }}</td>
                <td>
                  @if($pisteur->prix_unitaire)
                    {{ number_format($pisteur->prix_unitaire, 0, ',', ' ') }} FCFA
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($pisteur->date_debut && $pisteur->date_fin)
                    {{ $pisteur->date_debut->format('d/m/Y') }} - {{ $pisteur->date_fin->format('d/m/Y') }}
                  @elseif($pisteur->date_debut)
                    Depuis {{ $pisteur->date_debut->format('d/m/Y') }}
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $pisteur->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDelete{{ $pisteur->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4">
                  <i class="bx bx-leaf" style="font-size: 3rem; color: #ccc;"></i>
                  <p class="text-muted mb-0 mt-2">Aucun pisteur enregistré</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $pisteurs->links() }}
    </div>
  </div>
</div>

<!-- Modal Création -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-plus-circle me-2"></i>Ajouter un pisteur</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('pisteurs.store') }}" method="POST" id="formCreatePisteur">
        @csrf
        <div class="modal-body">
          <!-- Choix du mode -->
          <div class="mb-4">
            <label class="form-label fw-bold">Mode de création</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="mode_creation" id="modeAgent" value="agent" checked>
              <label class="btn btn-outline-primary" for="modeAgent">
                <i class="bx bx-user-check me-1"></i> Sélectionner un agent
              </label>
              <input type="radio" class="btn-check" name="mode_creation" id="modeManuel" value="manuel">
              <label class="btn btn-outline-primary" for="modeManuel">
                <i class="bx bx-edit me-1"></i> Création manuelle
              </label>
            </div>
          </div>

          <!-- Section Agent -->
          <div id="sectionAgent">
            <div class="mb-3">
              <label class="form-label">Sélectionner un agent <span class="text-danger">*</span></label>
              <select name="id_agent" id="selectAgent" class="form-select">
                <option value="">-- Choisir un agent --</option>
                @foreach($agents ?? [] as $agent)
                  <option value="{{ $agent['id_agent'] ?? '' }}" 
                          data-nom-complet="{{ $agent['nom_complet'] ?? '' }}"
                          data-numero="{{ $agent['numero_agent'] ?? '' }}">
                    {{ $agent['nom_complet'] ?? '' }} 
                    @if(!empty($agent['numero_agent'])) - {{ $agent['numero_agent'] }} @endif
                  </option>
                @endforeach
              </select>
              @if(empty($agents))
                <small class="text-warning">Impossible de charger la liste des agents.</small>
              @endif
            </div>
          </div>

          <!-- Section Manuelle -->
          <div id="sectionManuel" style="display: none;">
            <div class="mb-3">
              <label class="form-label">Nom <span class="text-danger">*</span></label>
              <input type="text" name="nom" id="inputNom" class="form-control" />
            </div>
            <div class="mb-3">
              <label class="form-label">Prénoms <span class="text-danger">*</span></label>
              <input type="text" name="prenoms" id="inputPrenoms" class="form-control" />
            </div>
            <div class="mb-3">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" id="inputContact" class="form-control" placeholder="Numéro de téléphone" />
            </div>
          </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeAgent = document.getElementById('modeAgent');
    const modeManuel = document.getElementById('modeManuel');
    const sectionAgent = document.getElementById('sectionAgent');
    const sectionManuel = document.getElementById('sectionManuel');
    const selectAgent = document.getElementById('selectAgent');
    const inputNom = document.getElementById('inputNom');
    const inputPrenoms = document.getElementById('inputPrenoms');
    const inputContact = document.getElementById('inputContact');

    function toggleSections() {
        if (modeAgent.checked) {
            sectionAgent.style.display = 'block';
            sectionManuel.style.display = 'none';
            selectAgent.required = true;
            inputNom.required = false;
            inputPrenoms.required = false;
        } else {
            sectionAgent.style.display = 'none';
            sectionManuel.style.display = 'block';
            selectAgent.required = false;
            selectAgent.value = '';
            inputNom.required = true;
            inputPrenoms.required = true;
        }
    }

    modeAgent.addEventListener('change', toggleSections);
    modeManuel.addEventListener('change', toggleSections);

    // Initialiser
    toggleSections();
});
</script>

@foreach($pisteurs as $pisteur)
<!-- Modal Édition -->
<div class="modal fade" id="modalEdit{{ $pisteur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Modifier le pisteur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('pisteurs.update', $pisteur) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" value="{{ $pisteur->nom }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Prénoms <span class="text-danger">*</span></label>
            <input type="text" name="prenoms" class="form-control" value="{{ $pisteur->prenoms }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Contact</label>
            <input type="text" name="contact" class="form-control" value="{{ $pisteur->contact }}" placeholder="Numéro de téléphone" />
          </div>
          <div class="mb-3">
            <label class="form-label">Prix unitaire (FCFA/tonne)</label>
            <input type="number" name="prix_unitaire" class="form-control" value="{{ $pisteur->prix_unitaire }}" placeholder="Prix unitaire" />
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ $pisteur->date_debut ? $pisteur->date_debut->format('Y-m-d') : '' }}" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ $pisteur->date_fin ? $pisteur->date_fin->format('Y-m-d') : '' }}" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">
            <i class="bx bx-save me-1"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Suppression -->
<div class="modal fade" id="modalDelete{{ $pisteur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-trash me-2"></i>Confirmer la suppression</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Voulez-vous vraiment supprimer <strong>{{ $pisteur->nom }} {{ $pisteur->prenoms }}</strong> ?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('pisteurs.destroy', $pisteur) }}" method="POST" class="d-inline">
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
