@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-time-five text-warning me-2"></i>Fiches de sortie non déchargées</h4>
      <span class="badge bg-warning fs-6">{{ $fiches->total() }} fiche(s) en attente</span>
    </div>

    <!-- Filtres de recherche -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-filter-alt me-2"></i>Filtres de recherche</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('fiches_sortie.non_dechargees') }}">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Véhicule</label>
              <select name="vehicule" class="form-select">
                <option value="">Tous les véhicules</option>
                @foreach($vehicules ?? [] as $v)
                  <option value="{{ $v['matricule_vehicule'] ?? '' }}" {{ request('vehicule') == ($v['matricule_vehicule'] ?? '') ? 'selected' : '' }}>
                    {{ $v['matricule_vehicule'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Pont</label>
              <select name="pont" class="form-select">
                <option value="">Tous les ponts</option>
                @foreach($ponts ?? [] as $p)
                  <option value="{{ $p['nom_pont'] ?? '' }}" {{ request('pont') == ($p['nom_pont'] ?? '') ? 'selected' : '' }}>
                    {{ $p['nom_pont'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Usine</label>
              <select name="usine" class="form-select">
                <option value="">Toutes les usines</option>
                @foreach($usines ?? [] as $u)
                  <option value="{{ $u['nom_usine'] ?? '' }}" {{ request('usine') == ($u['nom_usine'] ?? '') ? 'selected' : '' }}>
                    {{ $u['nom_usine'] ?? '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Date chargement début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Date chargement fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
            </div>
            <div class="col-md-9 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-search me-1"></i>Filtrer
              </button>
              <a href="{{ route('fiches_sortie.non_dechargees') }}" class="btn btn-outline-secondary">
                <i class="bx bx-refresh me-1"></i>Réinitialiser
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table table-hover">
          <thead class="table-warning">
            <tr>
              <th>Date chargement</th>
              <th>Véhicule</th>
              <th>Pont</th>
              <th>Agent</th>
              <th>Usine</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fiches as $f)
              <tr>
                <td>{{ $f->date_chargement ? $f->date_chargement->format('d-m-Y') : '-' }}</td>
                <td>
                  <a href="#" data-bs-toggle="modal" data-bs-target="#modalDechargement{{ $f->id }}" class="text-primary text-decoration-none">
                    <strong>{{ $f->matricule_vehicule }}</strong>
                  </a>
                </td>
                <td>{{ $f->nom_pont }}</td>
                <td>{{ $f->nom_agent }}</td>
                <td>{{ $f->usine ?? '-' }}</td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalDechargement{{ $f->id }}" title="Enregistrer déchargement">
                      <i class="bx bx-check-circle"></i> Décharger
                    </button>
                    <a href="{{ route('fiches_sortie.show', ['fiche_id' => $f->id]) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                      <i class="bx bx-show"></i>
                    </a>
                    <a href="{{ route('fiches_sortie.pdf', ['fiche_id' => $f->id]) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Imprimer PDF">
                      <i class="bx bx-printer"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4">
                  <i class="bx bx-check-circle text-success fs-1"></i>
                  <p class="mt-2 mb-0">Aucune fiche en attente de déchargement</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($fiches->hasPages())
      <div class="mt-4 d-flex justify-content-center">
        {{ $fiches->links() }}
      </div>
    @endif
  </div>
</div>

@foreach($fiches as $f)
<!-- Modal Déchargement -->
<div class="modal fade" id="modalDechargement{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bx bx-check-circle me-2"></i>Enregistrer le déchargement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.dechargement', ['fiche_id' => $f->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Véhicule:</strong> {{ $f->matricule_vehicule }}<br>
            <strong>Date chargement:</strong> {{ $f->date_chargement ? $f->date_chargement->format('d/m/Y') : '-' }}<br>
            <strong>Pont:</strong> {{ $f->nom_pont }}
          </div>
          <div class="mb-3">
            <label class="form-label">Date de déchargement <span class="text-danger">*</span></label>
            <input type="date" name="date_dechargement" class="form-control" value="{{ date('Y-m-d') }}" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Poids (kg)</label>
            <input type="number" name="poids_pont" class="form-control" value="{{ $f->poids_pont }}" placeholder="Poids en kg" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Confirmer le déchargement</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@endsection
