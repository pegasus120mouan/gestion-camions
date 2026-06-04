@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des Usines</h4>
    </div>

    <!-- Formulaire de recherche -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('usines.index') }}" class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Rechercher</label>
            <input type="text" name="search" class="form-control" placeholder="Nom usine..." value="{{ request('search') }}" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Produit</label>
            <select name="produit_id" class="form-select">
              <option value="">Tous les produits</option>
              @foreach($produits ?? [] as $produit)
                <option value="{{ $produit->id }}" {{ (string) request('produit_id') === (string) $produit->id ? 'selected' : '' }}>
                  {{ $produit->nom }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Rechercher</button>
            <a href="{{ route('usines.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header pb-0">
        <h5 class="mb-3"><i class="bx bx-list-ul me-2"></i>Produits</h5>

        @if(count($usinesParProduit ?? []) > 0 || (count($usinesNonClassees ?? []) > 0 && !request('produit_id')))
          <ul class="nav nav-tabs" id="produitsTabs" role="tablist">
            @foreach($usinesParProduit ?? [] as $index => $groupe)
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="tab-produit-{{ $groupe['id'] }}" data-bs-toggle="tab" data-bs-target="#content-produit-{{ $groupe['id'] }}" type="button" role="tab" aria-controls="content-produit-{{ $groupe['id'] }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                  <i class="bx bx-package me-1"></i>
                  {{ $groupe['nom'] }}
                </button>
              </li>
            @endforeach

            @if(count($usinesNonClassees ?? []) > 0 && !request('produit_id'))
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ count($usinesParProduit ?? []) === 0 ? 'active' : '' }}" id="tab-non-classees" data-bs-toggle="tab" data-bs-target="#content-non-classees" type="button" role="tab" aria-controls="content-non-classees" aria-selected="{{ count($usinesParProduit ?? []) === 0 ? 'true' : 'false' }}">
                  <i class="bx bx-folder me-1"></i>
                  Non classifiées
                </button>
              </li>
            @endif
          </ul>
        @endif
      </div>

      <div class="card-body">
        @if(!empty($external_error))
          <div class="alert alert-danger">{{ $external_error }}</div>
        @endif

        @if(count($usinesParProduit ?? []) > 0 || count($usinesNonClassees ?? []) > 0)
          <div class="tab-content" id="produitsTabsContent">
            @foreach($usinesParProduit ?? [] as $index => $groupe)
              <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="content-produit-{{ $groupe['id'] }}" role="tabpanel" aria-labelledby="tab-produit-{{ $groupe['id'] }}">
                <div class="row mb-3">
                  <div class="col-md-3">
                    <div class="d-flex align-items-center p-2 bg-light rounded">
                      <i class="bx bx-factory text-primary fs-4 me-2"></i>
                      <div>
                        <small class="text-muted">Usines</small>
                        <h6 class="mb-0">{{ count($groupe['usines']) }}</h6>
                      </div>
                    </div>
                  </div>
                </div>

                @if(count($groupe['usines']) > 0)
                  <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                      <thead class="table-light">
                        <tr>
                          <th>Nom usine</th>
                          <th>Code</th>
                          <th>Source</th>
                        </tr>
                      </thead>
                      <tbody class="table-border-bottom-0">
                        @foreach($groupe['usines'] as $u)
                          <tr>
                            <td><strong>{{ $u['nom_usine'] ?? '-' }}</strong></td>
                            <td><code>{{ $u['code_usine'] ?? '-' }}</code></td>
                            <td>
                              @if(($u['source'] ?? '') === 'local')
                                <span class="badge bg-label-primary">Attribuée au produit</span>
                              @else
                                <span class="badge bg-label-secondary">API</span>
                              @endif
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                @else
                  <div class="text-center py-4 text-muted">Aucune usine pour ce produit</div>
                @endif
              </div>
            @endforeach

            @if(count($usinesNonClassees ?? []) > 0 && !request('produit_id'))
              <div class="tab-pane fade {{ count($usinesParProduit ?? []) === 0 ? 'show active' : '' }}" id="content-non-classees" role="tabpanel" aria-labelledby="tab-non-classees">
                <div class="row mb-3">
                  <div class="col-md-3">
                    <div class="d-flex align-items-center p-2 bg-light rounded">
                      <i class="bx bx-factory text-secondary fs-4 me-2"></i>
                      <div>
                        <small class="text-muted">Usines</small>
                        <h6 class="mb-0">{{ count($usinesNonClassees) }}</h6>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="table-responsive text-nowrap">
                  <table class="table table-hover">
                    <thead class="table-light">
                      <tr>
                        <th>Nom Usine</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      @foreach($usinesNonClassees as $u)
                        <tr>
                          <td><strong>{{ $u['nom_usine'] ?? '-' }}</strong></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif
          </div>
        @else
          <div class="text-center py-4">Aucune usine trouvée</div>
        @endif
      </div>
    </div>

    @if(!empty($pagination))
      @php
        $currentPage = (int) ($pagination['current_page'] ?? 1);
        $lastPage = (int) ($pagination['last_page'] ?? 1);
        $total = (int) ($pagination['total'] ?? 0);
      @endphp
      @if($lastPage > 1)
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
              <a class="page-link" href="{{ route('usines.index', ['page' => $currentPage - 1, 'search' => request('search'), 'produit_id' => request('produit_id')]) }}">Précédent</a>
            </li>
            @for($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++)
              <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route('usines.index', ['page' => $i, 'search' => request('search'), 'produit_id' => request('produit_id')]) }}">{{ $i }}</a>
              </li>
            @endfor
            <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
              <a class="page-link" href="{{ route('usines.index', ['page' => $currentPage + 1, 'search' => request('search'), 'produit_id' => request('produit_id')]) }}">Suivant</a>
            </li>
          </ul>
          <p class="text-center text-muted">Page {{ $currentPage }} sur {{ $lastPage }} ({{ $total }} usines)</p>
        </nav>
      @endif
    @endif

    @if(count($usines) > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Résumé</h5>
          <p><strong>Total usines:</strong> {{ count($usines) }}</p>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
