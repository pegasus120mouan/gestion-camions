@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-check-circle text-success me-2"></i>Fiches de sortie déchargées</h4>
      <span class="badge bg-success fs-6">{{ $fiches->total() }} fiche(s) déchargée(s)</span>
    </div>

    <!-- Filtres de recherche -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-filter-alt me-2"></i>Filtres de recherche</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('fiches_sortie.dechargees') }}">
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
              <label class="form-label">Date déchargement début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Date déchargement fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
            </div>
            <div class="col-md-9 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-search me-1"></i>Filtrer
              </button>
              <a href="{{ route('fiches_sortie.dechargees') }}" class="btn btn-outline-secondary">
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
          <thead class="table-success">
            <tr>
              <th>Date chargement</th>
              <th>Date déchargement</th>
              <th>Véhicule</th>
              <th>Pont</th>
              <th>Agent</th>
              <th>Usine</th>
              <th>Poids (kg)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($fiches as $f)
              <tr>
                <td>{{ $f->date_chargement ? $f->date_chargement->format('d-m-Y') : '-' }}</td>
                <td>
                  <span class="text-success">
                    <i class="bx bx-check-circle me-1"></i>{{ $f->date_dechargement->format('d-m-Y') }}
                  </span>
                </td>
                <td><strong>{{ $f->matricule_vehicule }}</strong></td>
                <td>{{ $f->nom_pont }}</td>
                <td>{{ $f->nom_agent }}</td>
                <td>{{ $f->usine ?? '-' }}</td>
                <td>
                  @if($f->poids_pont)
                    {{ number_format((float)$f->poids_pont, 0, ',', ' ') }}
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
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
                <td colspan="8" class="text-center py-4">
                  <i class="bx bx-package text-muted fs-1"></i>
                  <p class="mt-2 mb-0">Aucune fiche déchargée trouvée</p>
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
@endsection
