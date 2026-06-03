@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('particuliers.prix.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour à la liste
        </a>
        <h4 class="mb-0">
          <i class="bx bx-user text-primary me-2"></i>{{ $agent->nom_complet }}
        </h4>
        <small class="text-muted">
          N° {{ $agent->numero_agent }}
          @if($agent->groupe) | Groupe : {{ $agent->groupe->nom_groupe }} @endif
          @if($agent->contact) | Contact : {{ $agent->contact }} @endif
        </small>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddPrix">
        <i class="bx bx-plus me-1"></i>Ajouter un prix
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

    <div class="row">
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label text-muted">N° agent</label>
              <p class="fw-bold mb-0">{{ $agent->numero_agent }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Nom complet</label>
              <p class="fw-bold mb-0">{{ $agent->nom_complet }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Groupe</label>
              <p class="fw-bold mb-0">{{ $agent->groupe?->nom_groupe ?? '-' }}</p>
            </div>
            <div class="mb-0">
              <label class="form-label text-muted">Contact</label>
              <p class="fw-bold mb-0">{{ $agent->contact ?? '-' }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix unitaire par usine</h5>
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
                @forelse($agent->prix as $prix)
                  <tr>
                    <td><strong>{{ $prix->nom_usine }}</strong></td>
                    <td class="text-end">{{ number_format($prix->prix, 0, ',', ' ') }}</td>
                    <td>{{ $prix->date_debut ? $prix->date_debut->format('d-m-Y') : '-' }}</td>
                    <td>{{ $prix->date_fin ? $prix->date_fin->format('d-m-Y') : '-' }}</td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPrix{{ $prix->id }}">
                        <i class="bx bx-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('particuliers.prix.delete', ['agent' => $agent, 'prix' => $prix]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce prix ?')">
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
                    <td colspan="5" class="text-center text-muted py-4">Aucun prix unitaire configuré</td>
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

<div class="modal fade" id="modalAddPrix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('particuliers.prix.store', $agent) }}" id="formAddPrix">
        @csrf
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Ajouter un prix unitaire</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Usine <span class="text-danger">*</span></label>
            <select name="id_usine" class="form-select" required onchange="updateNomUsinePrix(this)">
              <option value="">Sélectionner une usine</option>
              <option value="all" data-nom="TOUTES LES USINES">TOUTES LES USINES</option>
              @foreach($usines as $usine)
                <option value="{{ $usine['id_usine'] ?? '' }}" data-nom="{{ $usine['nom_usine'] ?? '' }}">{{ $usine['nom_usine'] ?? '' }}</option>
              @endforeach
            </select>
            <input type="hidden" name="nom_usine" value="">
            <input type="hidden" name="toutes_usines" value="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" step="0.01" placeholder="Ex: 50">
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

@foreach($agent->prix as $prix)
<div class="modal fade" id="modalEditPrix{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('particuliers.prix.update', ['agent' => $agent, 'prix' => $prix]) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Modifier — {{ $prix->nom_usine }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" step="0.01" value="{{ $prix->prix }}">
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

<script>
function updateNomUsinePrix(select) {
  var form = select.closest('form');
  var selectedOption = select.options[select.selectedIndex];
  form.querySelector('input[name="nom_usine"]').value = selectedOption.dataset.nom || '';
  form.querySelector('input[name="toutes_usines"]').value = select.value === 'all' ? '1' : '0';
}
</script>
@endsection
