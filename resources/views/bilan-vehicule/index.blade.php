@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>Bilan par Camion</h4>
    </div>

    <!-- Statistiques globales -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-3" style="background: rgba(255,255,255,0.2); border-radius: 10px;">
                <i class="bx bx-category fs-3 text-white"></i>
              </div>
              <div>
                <h6 class="text-white mb-0">Catégories</h6>
                <h3 class="text-white mb-0">{{ $categories->count() }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-3" style="background: rgba(255,255,255,0.2); border-radius: 10px;">
                <i class="bx bx-car fs-3 text-white"></i>
              </div>
              <div>
                <h6 class="text-white mb-0">Véhicules</h6>
                <h3 class="text-white mb-0">{{ $categories->sum('nb_vehicules') }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-3" style="background: rgba(255,255,255,0.2); border-radius: 10px;">
                <i class="bx bx-file fs-3 text-white"></i>
              </div>
              <div>
                <h6 class="text-white mb-0">Fiches de sortie</h6>
                <h3 class="text-white mb-0">{{ $categories->sum('nb_fiches') }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Onglets des catégories -->
    <div class="card">
      <div class="card-header pb-0">
        <h5 class="mb-3"><i class="bx bx-list-ul me-2"></i>Catégories de véhicules</h5>
        
        <!-- Onglets horizontaux -->
        <ul class="nav nav-tabs" id="categoriesTabs" role="tablist">
          @forelse($categories as $index => $categorie)
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="tab-{{ $categorie->id }}" data-bs-toggle="tab" data-bs-target="#content-{{ $categorie->id }}" type="button" role="tab" aria-controls="content-{{ $categorie->id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
              <i class="bx bx-folder me-1"></i>
              {{ $categorie->nom }}
            </button>
          </li>
          @empty
          @endforelse
        </ul>
      </div>
      
      <div class="card-body">
        <!-- Contenu des onglets -->
        <div class="tab-content" id="categoriesTabsContent">
          @forelse($categories as $index => $categorie)
          <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="content-{{ $categorie->id }}" role="tabpanel" aria-labelledby="tab-{{ $categorie->id }}">
            
            <!-- Statistiques de la catégorie -->
            <div class="row mb-3">
              <div class="col-md-2">
                <div class="d-flex align-items-center p-2 bg-light rounded">
                  <i class="bx bx-car text-primary fs-4 me-2"></i>
                  <div>
                    <small class="text-muted">Véhicules</small>
                    <h6 class="mb-0">{{ $categorie->nb_vehicules }}</h6>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <div class="d-flex align-items-center p-2 bg-light rounded">
                  <i class="bx bx-file text-info fs-4 me-2"></i>
                  <div>
                    <small class="text-muted">Fiches</small>
                    <h6 class="mb-0">{{ $categorie->nb_fiches }}</h6>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <div class="d-flex align-items-center p-2 bg-light rounded">
                  <i class="bx bx-package text-warning fs-4 me-2"></i>
                  <div>
                    <small class="text-muted">Poids livré</small>
                    <h6 class="mb-0">{{ number_format($categorie->total_poids, 0, ',', ' ') }} kg</h6>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <div class="d-flex align-items-center p-2 bg-light rounded">
                  <i class="bx bx-dollar-circle text-success fs-4 me-2"></i>
                  <div>
                    <small class="text-muted">Montant</small>
                    <h6 class="mb-0 text-success">{{ number_format($categorie->total_montant_camion, 0, ',', ' ') }} F</h6>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <div class="d-flex align-items-center p-2 bg-light rounded">
                  <i class="bx bx-money text-danger fs-4 me-2"></i>
                  <div>
                    <small class="text-muted">Dépenses</small>
                    <h6 class="mb-0 text-danger">{{ number_format($categorie->total_carburant + $categorie->total_frais_route, 0, ',', ' ') }} F</h6>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <div class="d-flex align-items-center p-2 bg-light rounded">
                  <i class="bx bx-trending-up fs-4 me-2 {{ ($categorie->total_montant_camion - $categorie->total_carburant - $categorie->total_frais_route) >= 0 ? 'text-success' : 'text-danger' }}"></i>
                  <div>
                    <small class="text-muted">Marge</small>
                    @php $margeCat = $categorie->total_montant_camion - $categorie->total_carburant - $categorie->total_frais_route; @endphp
                    <h6 class="mb-0 {{ $margeCat >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($margeCat, 0, ',', ' ') }} F</h6>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tableau des véhicules -->
            @if($categorie->vehicules->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Matricule</th>
                    <th class="text-center">Fiches</th>
                    <th class="text-end">Poids</th>
                    <th class="text-end">Montant</th>
                    <th class="text-end">Dépenses</th>
                    <th class="text-end">Marge</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($categorie->vehicules as $vehicule)
                  <tr>
                    <td>
                      <i class="bx bx-car me-2 text-primary"></i>
                      <strong>{{ $vehicule->matricule_vehicule }}</strong>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-info">{{ $vehicule->nb_fiches }}</span>
                    </td>
                    <td class="text-end">
                      <strong>{{ number_format($vehicule->total_poids, 0, ',', ' ') }} kg</strong>
                    </td>
                    <td class="text-end">
                      <strong class="text-success">{{ number_format($vehicule->total_montant_camion, 0, ',', ' ') }} F</strong>
                    </td>
                    <td class="text-end">
                      <strong class="text-danger">{{ number_format($vehicule->total_depenses, 0, ',', ' ') }} F</strong>
                    </td>
                    <td class="text-end">
                      <strong class="{{ $vehicule->marge >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($vehicule->marge, 0, ',', ' ') }} F
                      </strong>
                    </td>
                    <td class="text-center">
                      <a href="{{ route('bilan-vehicule.show', ['vehicule_id' => $vehicule->vehicule_id]) }}" class="btn btn-sm btn-outline-primary" title="Voir détails">
                        <i class="bx bx-show"></i>
                      </a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @else
            <div class="text-center py-5">
              <i class="bx bx-car text-muted" style="font-size: 3rem;"></i>
              <p class="text-muted mt-2 mb-0">Aucun véhicule dans cette catégorie</p>
            </div>
            @endif
          </div>
          @empty
          <div class="text-center py-5">
            <i class="bx bx-folder-open text-muted" style="font-size: 4rem;"></i>
            <p class="text-muted mt-3">Aucune catégorie de véhicule trouvée</p>
          </div>
          @endforelse
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
