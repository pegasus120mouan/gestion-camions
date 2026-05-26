@extends('layout.main')
@section('content')

          <div class="content-wrapper">
            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="row">
                <div class="col-xxl-8 mb-6 order-0">
                  <div class="card">
                    <div class="d-flex align-items-start row">
                      <div class="col-sm-7">
                        <div class="card-body">
                          @if(!empty($soldeChef))
                            <h5 class="card-title text-primary mb-2">Solde actuel</h5>
                            @if(!empty($soldeChef['nom']))
                              <p class="text-muted small mb-3">{{ trim($soldeChef['nom'] . ' ' . $soldeChef['prenoms']) }}</p>
                            @endif
                            <h2 class="mb-4 {{ ($soldeChef['reste_a_payer'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                              {{ number_format($soldeChef['reste_a_payer'], 0, ',', ' ') }} FCFA
                            </h2>
                            <p class="text-muted small mb-0">Reste à payer (votre solde disponible)</p>
                          @elseif(!empty($soldeChefError))
                            <h5 class="card-title text-warning mb-3">Solde chef d'équipe</h5>
                            <p class="mb-4 text-muted">{{ $soldeChefError }}</p>
                            <small class="text-muted">Vérifiez le token chef (utilisateur ou CHEF_EQUIPE_TOKEN dans .env).</small>
                          @else
                            <h5 class="card-title text-primary mb-3">Solde chef d'équipe</h5>
                            <p class="mb-4 text-muted">
                              Aucun token configuré.<br />
                              Définissez <code>chef_equipe_token</code> sur l'utilisateur ou <code>CHEF_EQUIPE_TOKEN</code> dans .env.
                            </p>
                          @endif
                        </div>
                      </div>
                      <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-6">
                          <img
                            src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}"
                            height="175"
                            alt="View Badge User" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
                  <div class="row">
                    <div class="col-lg-6 col-md-12 col-6 mb-6">
                      <div class="card h-100">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                              <img
                                src="{{ asset('assets/img/icons/unicons/chart-success.png') }}"
                                alt="chart success"
                                class="rounded" />
                            </div>
                            <div class="dropdown">
                              <button
                                class="btn p-0"
                                type="button"
                                id="cardOpt3"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                                <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                              </button>
                              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                              </div>
                            </div>
                          </div>
                          <p class="mb-1">Nombre de camions</p>
                          <h4 class="card-title mb-3">{{ $nombreCamions ?? 0 }}</h4>
                          <small class="text-muted fw-medium">Véhicules enregistrés</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-6 mb-6">
                      <div class="card h-100">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                              <img
                                src="{{ asset('assets/img/icons/unicons/wallet-info.png') }}"
                                alt="wallet info"
                                class="rounded" />
                            </div>
                            <div class="dropdown">
                              <button
                                class="btn p-0"
                                type="button"
                                id="cardOpt6"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                                <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                              </button>
                              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                              </div>
                            </div>
                          </div>
                          <p class="mb-1">Depenses</p>
                          <h4 class="card-title mb-3">{{ number_format($totalDepenses ?? 0, 0, ',', ' ') }} FCFA</h4>
                          <small class="text-muted fw-medium">Total des depenses</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Stock Disponible -->
                <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6">
                  <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                      <div class="card-title mb-0">
                        <h5 class="m-0 me-2"><i class="bx bx-package text-primary me-2"></i>Stock Disponible par Pont</h5>
                      </div>
                      <a href="{{ route('ponts.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-show me-1"></i>Voir détails
                      </a>
                    </div>
                    <div class="card-body">
                      <!-- Résumé global -->
                      <div class="row mb-4">
                        <div class="col-md-4">
                          <div class="d-flex align-items-center p-3 rounded" style="background: linear-gradient(135deg, #28c76f22 0%, #28c76f11 100%);">
                            <div class="avatar me-3">
                              <span class="avatar-initial rounded bg-success">
                                <i class="bx bx-down-arrow-circle text-white"></i>
                              </span>
                            </div>
                            <div>
                              <small class="text-muted d-block">Total Entrées</small>
                              <h5 class="mb-0 text-success">{{ number_format($totalStockEntrees ?? 0, 0, ',', ' ') }} kg</h5>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="d-flex align-items-center p-3 rounded" style="background: linear-gradient(135deg, #ea545522 0%, #ea545511 100%);">
                            <div class="avatar me-3">
                              <span class="avatar-initial rounded bg-danger">
                                <i class="bx bx-up-arrow-circle text-white"></i>
                              </span>
                            </div>
                            <div>
                              <small class="text-muted d-block">Total Sorties</small>
                              <h5 class="mb-0 text-danger">{{ number_format($totalStockSorties ?? 0, 0, ',', ' ') }} kg</h5>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="d-flex align-items-center p-3 rounded" style="background: linear-gradient(135deg, #7367f022 0%, #7367f011 100%);">
                            <div class="avatar me-3">
                              <span class="avatar-initial rounded bg-primary">
                                <i class="bx bx-package text-white"></i>
                              </span>
                            </div>
                            <div>
                              <small class="text-muted d-block">Stock Disponible</small>
                              <h5 class="mb-0 text-primary">{{ number_format($totalStockDisponible ?? 0, 0, ',', ' ') }} kg</h5>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Liste des ponts avec stock -->
                      @if(count($stocksParPont ?? []) > 0)
                        <div class="table-responsive">
                          <table class="table table-hover">
                            <thead>
                              <tr>
                                <th>Pont</th>
                                <th class="text-end">Entrées</th>
                                <th class="text-end">Sorties</th>
                                <th class="text-end">Disponible</th>
                                <th class="text-center">Utilisation</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach($stocksParPont as $stock)
                                @php
                                  $pourcentage = $stock['entrees'] > 0 ? round(($stock['sorties'] / $stock['entrees']) * 100) : 0;
                                  $progressClass = $pourcentage > 80 ? 'bg-danger' : ($pourcentage > 50 ? 'bg-warning' : 'bg-success');
                                @endphp
                                <tr>
                                  <td>
                                    <div class="d-flex align-items-center">
                                      <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-primary">
                                          <i class="bx bx-map"></i>
                                        </span>
                                      </div>
                                      <strong>{{ $stock['nom_pont'] }}</strong>
                                    </div>
                                  </td>
                                  <td class="text-end text-success">{{ number_format($stock['entrees'], 0, ',', ' ') }} kg</td>
                                  <td class="text-end text-danger">{{ number_format($stock['sorties'], 0, ',', ' ') }} kg</td>
                                  <td class="text-end">
                                    <span class="badge bg-primary">{{ number_format($stock['disponible'], 0, ',', ' ') }} kg</span>
                                  </td>
                                  <td>
                                    <div class="d-flex align-items-center justify-content-center">
                                      <div class="progress w-100" style="height: 8px;">
                                        <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $pourcentage }}%"></div>
                                      </div>
                                      <small class="ms-2 text-muted">{{ $pourcentage }}%</small>
                                    </div>
                                  </td>
                                </tr>
                              @endforeach
                            </tbody>
                          </table>
                        </div>
                      @else
                        <div class="text-center py-4">
                          <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                          <p class="text-muted mt-2 mb-0">Aucun stock enregistré</p>
                          <a href="{{ route('ponts.index') }}" class="btn btn-sm btn-primary mt-2">
                            <i class="bx bx-plus me-1"></i>Ajouter du stock
                          </a>
                        </div>
                      @endif
                    </div>
                  </div>
                </div>
                <!--/ Stock Disponible -->
                <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 profile-report">
                  <div class="row">
                    <div class="col-6 mb-6 payments">
                      <div class="card h-100">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                              <img src="{{ asset('assets/img/icons/unicons/paypal.png') }}" alt="paypal" class="rounded" />
                            </div>
                            <div class="dropdown">
                              <button
                                class="btn p-0"
                                type="button"
                                id="cardOpt4"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                                <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                              </button>
                              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                              </div>
                            </div>
                          </div>
                          <p class="mb-1">Nombre de tickets</p>
                          <h4 class="card-title mb-3">{{ $nombreTickets ?? 0 }}</h4>
                          <small class="text-muted fw-medium">Tickets enregistres</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-6 mb-6 transactions">
                      <div class="card h-100">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                              <img src="{{ asset('assets/img/icons/unicons/cc-primary.png') }}" alt="Credit Card" class="rounded" />
                            </div>
                            <div class="dropdown">
                              <button
                                class="btn p-0"
                                type="button"
                                id="cardOpt1"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                                <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                              </button>
                              <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                              </div>
                            </div>
                          </div>
                          <p class="mb-1">Transactions</p>
                          <h4 class="card-title mb-3">$14,857</h4>
                          <small class="text-success fw-medium"
                            ><i class="icon-base bx bx-up-arrow-alt"></i> +28.14%</small
                          >
                        </div>
                      </div>
                    </div>
                    <div class="col-12 mb-6">
                      <div class="card h-100" style="background: linear-gradient(135deg, #ff9f4322 0%, #ff9f4311 100%);">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <div class="card-title mb-3">
                                <h5 class="text-nowrap mb-1">
                                  <i class="bx bx-time-five text-warning me-1"></i>
                                  Fiches en attente
                                </h5>
                                <span class="badge bg-warning">Non déchargées</span>
                              </div>
                              <div>
                                <h2 class="mb-1 text-warning">{{ $fichesNonDechargees ?? 0 }}</h2>
                                <small class="text-muted">sur {{ $totalFiches ?? 0 }} fiches au total</small>
                              </div>
                            </div>
                            <div class="text-end">
                              <div class="avatar avatar-lg">
                                <span class="avatar-initial rounded-circle bg-warning">
                                  <i class="bx bx-file text-white" style="font-size: 1.5rem;"></i>
                                </span>
                              </div>
                              <a href="{{ route('fiches_sortie.non_dechargees') }}" class="btn btn-sm btn-warning mt-3">
                                <i class="bx bx-show me-1"></i>Voir
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <!-- Order Statistics -->
                <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
                  <div class="card h-100">
                    <div class="card-header d-flex justify-content-between">
                      <div class="card-title mb-0">
                        <h5 class="mb-1 me-2">Order Statistics</h5>
                        <p class="card-subtitle">42.82k Total Sales</p>
                      </div>
                      <div class="dropdown">
                        <button
                          class="btn text-body-secondary p-0"
                          type="button"
                          id="orederStatistics"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                          <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="orederStatistics">
                          <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                          <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                          <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        </div>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-6">
                        <div class="d-flex flex-column align-items-center gap-1">
                          <h3 class="mb-1">8,258</h3>
                          <small>Total Orders</small>
                        </div>
                        <div id="orderStatisticsChart"></div>
                      </div>
                      <ul class="p-0 m-0">
                        <li class="d-flex align-items-center mb-5">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary"
                              ><i class="icon-base bx bx-mobile-alt"></i
                            ></span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <h6 class="mb-0">Electronic</h6>
                              <small>Mobile, Earbuds, TV</small>
                            </div>
                            <div class="user-progress">
                              <h6 class="mb-0">82.5k</h6>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center mb-5">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success"
                              ><i class="icon-base bx bx-closet"></i
                            ></span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <h6 class="mb-0">Fashion</h6>
                              <small>T-shirt, Jeans, Shoes</small>
                            </div>
                            <div class="user-progress">
                              <h6 class="mb-0">23.8k</h6>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center mb-5">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info"
                              ><i class="icon-base bx bx-home-alt"></i
                            ></span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <h6 class="mb-0">Decor</h6>
                              <small>Fine Art, Dining</small>
                            </div>
                            <div class="user-progress">
                              <h6 class="mb-0">849k</h6>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-secondary"
                              ><i class="icon-base bx bx-football"></i
                            ></span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <h6 class="mb-0">Sports</h6>
                              <small>Football, Cricket Kit</small>
                            </div>
                            <div class="user-progress">
                              <h6 class="mb-0">99</h6>
                            </div>
                          </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <!--/ Order Statistics -->

                <!-- Expense Overview -->
                <div class="col-md-6 col-lg-4 order-1 mb-6">
                  <div class="card h-100">
                    <div class="card-header nav-align-top">
                      <ul class="nav nav-pills flex-wrap row-gap-2" role="tablist">
                        <li class="nav-item">
                          <button
                            type="button"
                            class="nav-link active"
                            role="tab"
                            data-bs-toggle="tab"
                            data-bs-target="#navs-tabs-line-card-income"
                            aria-controls="navs-tabs-line-card-income"
                            aria-selected="true">
                            Income
                          </button>
                        </li>
                        <li class="nav-item">
                          <button type="button" class="nav-link" role="tab">Expenses</button>
                        </li>
                        <li class="nav-item">
                          <button type="button" class="nav-link" role="tab">Profit</button>
                        </li>
                      </ul>
                    </div>
                    <div class="card-body">
                      <div class="tab-content p-0">
                        <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                          <div class="d-flex mb-6">
                            <div class="avatar flex-shrink-0 me-3">
                              <img src="{{ asset('assets/img/icons/unicons/wallet.png') }}" alt="User" />
                            </div>
                            <div>
                              <p class="mb-0">Total Balance</p>
                              <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">$459.10</h6>
                                <small class="text-success fw-medium">
                                  <i class="icon-base bx bx-chevron-up icon-lg"></i>
                                  42.9%
                                </small>
                              </div>
                            </div>
                          </div>
                          <div id="incomeChart"></div>
                          <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                            <div class="flex-shrink-0">
                              <div id="expensesOfWeek"></div>
                            </div>
                            <div>
                              <h6 class="mb-0">Income this week</h6>
                              <small>$39k less than last week</small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Expense Overview -->

                <!-- Transactions Financières -->
                <div class="col-md-6 col-lg-4 order-2 mb-6">
                  <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                      <h5 class="card-title m-0 me-2">
                        <i class="bx bx-wallet text-primary me-1"></i>
                        Transactions
                      </h5>
                      <a href="{{ route('depenses.liste') }}" class="btn btn-sm btn-outline-primary">
                        Voir tout
                      </a>
                    </div>
                    <div class="card-body pt-4">
                      <ul class="p-0 m-0" style="list-style: none;">
                        @forelse($dernieresDepenses ?? [] as $depense)
                          <li class="d-flex align-items-center {{ !$loop->last ? 'mb-4' : '' }}">
                            <div class="avatar flex-shrink-0 me-3">
                              <span class="avatar-initial rounded bg-danger">
                                <i class="bx bx-minus text-white"></i>
                              </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                              <div class="me-2">
                                <small class="d-block text-muted">{{ $depense->type ?? 'Dépense' }}</small>
                                <h6 class="fw-normal mb-0">{{ $depense->description ?? 'Sans description' }}</h6>
                              </div>
                              <div class="text-end">
                                <h6 class="fw-normal mb-0 text-danger">-{{ number_format($depense->montant ?? 0, 0, ',', ' ') }}</h6>
                                <small class="text-muted">FCFA</small>
                              </div>
                            </div>
                          </li>
                        @empty
                          <li class="text-center text-muted py-4">
                            <i class="bx bx-wallet" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2">Aucune transaction</p>
                          </li>
                        @endforelse
                      </ul>
                    </div>
                  </div>
                </div>
                <!--/ Transactions Financières -->
              </div>
            </div>
            <!-- / Content -->

            <!-- Footer -->


            <div class="content-backdrop fade"></div>
          </div>
@endsection
