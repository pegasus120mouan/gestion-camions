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
            <div class="mb-3">
              <label class="form-label text-muted">Contact</label>
              <p class="fw-bold mb-0">{{ $agent->contact ?? '-' }}</p>
            </div>
          </div>
        </div>

        {{-- Carte Codes Transporteurs --}}
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-header bg-gradient-info text-white" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);">
            <h5 class="mb-0 text-white"><i class="bx bx-truck me-2"></i>Codes Transporteurs</h5>
          </div>
          <div class="card-body p-3">
            <div class="d-flex flex-column gap-3">
              @foreach($codesTransporteurs as $code)
                @php
                  $sectionId = 'section-' . Str::slug($code->nom);
                  $countPrix = $agent->prix->where('type_transporteur', $code->nom)->count();

                  // Couleurs personnalisées selon le type
                  $colors = match(true) {
                    str_contains($code->nom, 'PGF') => ['#ff9500', '#ffb347', '#fff5e6'], // Orange doré
                    str_contains($code->nom, 'Pisteur') || str_contains($code->nom, 'pisteur') => ['#00c6ff', '#0072ff', '#e6f7ff'], // Bleu cyan
                    default => ['#6c757d', '#adb5bd', '#f8f9fa'], // Gris
                  };
                @endphp
                <a href="javascript:void(0)" onclick="showTransporteurSection('{{ $code->nom }}')" class="text-decoration-none">
                  <div class="transporteur-card d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm"
                       style="background: {{ $colors[2] }}; border-left: 5px solid {{ $colors[0] }}; transition: all 0.3s ease; cursor: pointer;"
                       onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)';"
                       onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                    <div class="d-flex align-items-center">
                      <div class="icon-circle me-3 d-flex align-items-center justify-content-center"
                           style="width: 45px; height: 45px; background: linear-gradient(135deg, {{ $colors[0] }} 0%, {{ $colors[1] }} 100%); border-radius: 50%; box-shadow: 0 4px 10px {{ $colors[0] }}40;">
                        <i class="bx bx-truck text-white fs-5"></i>
                      </div>
                      <div>
                        <h6 class="mb-0 fw-bold" style="color: {{ $colors[0] }};">{{ $code->nom }}</h6>
                        <small class="text-muted">{{ $countPrix }} prix configuré{{ $countPrix > 1 ? 's' : '' }}</small>
                      </div>
                    </div>
                    <i class="bx bx-chevron-right fs-4" style="color: {{ $colors[0] }};"></i>
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8" id="prixContainer">
        @php
          $types = $codesTransporteurs->pluck('nom', 'nom')->toArray();
        @endphp

        {{-- Section Tous les prix (prix sans type) --}}
        @php
          $prixSansType = $agent->prix->filter(fn($p) => empty($p->type_transporteur));
          $prixParProduitSansType = $prixSansType->groupBy('produit_id');
        @endphp
        @if($prixParProduitSansType->isNotEmpty())
          <div class="card mb-4 prix-section" id="section-tous" data-type="tous" style="display: none;">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Tous les prix</h5>
              <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix">
                <i class="bx bx-plus me-1"></i>Ajouter
              </button>
            </div>
            <div class="card-body p-3">
              <p class="text-muted small mb-3"><i class="bx bx-info-circle me-1"></i>Prix sans type de transporteur spécifique — Cliquez sur un produit</p>

              {{-- Tags produits horizontaux --}}
              <div class="d-flex flex-wrap gap-2 mb-4">
                @foreach($prixParProduitSansType as $produitId => $prixList)
                  @php
                    $produit = $produits->firstWhere('id', $produitId);
                    $produitNom = $produit?->nom ?? 'Produit non défini';
                    $produitColors = match(true) {
                      str_contains($produitNom, 'Palmier') => ['#22c55e', '#86efac', '#f0fdf4'],
                      str_contains($produitNom, 'Coton') => ['#eab308', '#fde047', '#fefce8'],
                      default => ['#3b82f6', '#93c5fd', '#eff6ff'],
                    };
                  @endphp

                  <button type="button"
                          class="btn btn-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2"
                          onclick="toggleProduitTable('table-tous-{{ $loop->index }}', this)"
                          style="background: {{ $produitColors[2] }}; border: 2px solid {{ $produitColors[0] }}; color: {{ $produitColors[0] }};">
                    <i class="bx bx-package"></i>
                    <span class="fw-bold">{{ $produitNom }}</span>
                    <span class="badge rounded-pill ms-1" style="background: {{ $produitColors[0] }}; color: white;">{{ $prixList->count() }}</span>
                  </button>
                @endforeach
              </div>

              {{-- Tables des produits --}}
              @foreach($prixParProduitSansType as $produitId => $prixList)
                @php
                  $produit = $produits->firstWhere('id', $produitId);
                  $produitNom = $produit?->nom ?? 'Produit non défini';
                  $produitColors = match(true) {
                    str_contains($produitNom, 'Palmier') => ['#22c55e', '#86efac', '#f0fdf4'],
                    str_contains($produitNom, 'Coton') => ['#eab308', '#fde047', '#fefce8'],
                    default => ['#3b82f6', '#93c5fd', '#eff6ff'],
                  };
                @endphp

                  @php
                    $nbPrixTous = $prixList->count();
                    $totalPages = (int) ceil($nbPrixTous / 10);
                  @endphp
                <div id="table-tous-{{ $loop->index }}" class="produit-table mb-4" data-current-page="1" data-total-pages="{{ max(1, $totalPages) }}">
                  <div class="d-flex justify-content-between align-items-center p-3 rounded-top" style="background: {{ $produitColors[0] }};">
                    <span class="text-white fw-bold">
                      <i class="bx bx-list-ul me-2"></i>{{ $produitNom }} —
                      <span class="prix-count-visible">{{ $nbPrixTous }}</span> / {{ $nbPrixTous }} prix
                    </span>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix" onclick="setTypeAndProduit('', {{ $produitId ?? 'null' }})">
                      <i class="bx bx-plus me-1"></i>Ajouter
                    </button>
                  </div>
                  @include('shared._prix_table_filtres')
                  <div class="alert alert-warning py-2 mb-0 mx-0 rounded-0 prix-filtre-empty d-none">
                    Aucun prix ne correspond à votre recherche.
                  </div>
                  <div class="table-responsive border border-top-0 rounded-bottom" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                      <thead class="table-light sticky-top">
                        <tr><th class="ps-3">USINE</th><th class="text-end">PRIX</th><th>DÉBUT</th><th>FIN</th><th class="text-center pe-3">ACTIONS</th></tr>
                      </thead>
                      <tbody>
                        @foreach($prixList as $index => $prix)
                          <tr class="prix-row" data-row-index="{{ $index }}"
                              data-usine="{{ mb_strtolower($prix->nom_usine ?? '', 'UTF-8') }}"
                              data-date-debut="{{ $prix->date_debut ? $prix->date_debut->format('Y-m-d') : '' }}"
                              data-date-fin="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}">
                            <td class="ps-3"><strong>{{ $prix->nom_usine }}</strong></td>
                            <td class="text-end"><span class="badge bg-success">{{ number_format($prix->prix, 0, ',', ' ') }} FCFA</span></td>
                            <td><small>{{ $prix->date_debut ? $prix->date_debut->format('d-m-Y') : '-' }}</small></td>
                            <td><small>{{ $prix->date_fin ? $prix->date_fin->format('d-m-Y') : '-' }}</small></td>
                            <td class="text-center pe-3">
                              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPrix{{ $prix->id }}"><i class="bx bx-edit"></i></button>
                              <form method="POST" action="{{ route('particuliers.prix.delete', ['agent' => $agent, 'prix' => $prix]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce prix ?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button></form>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                  <div class="p-2 border border-top-0 rounded-bottom bg-light d-flex justify-content-center align-items-center gap-2 pagination-controls" style="{{ $totalPages > 1 ? '' : 'display:none;' }}">
                    <button class="btn btn-sm btn-outline-secondary btn-prev" onclick="changePage('table-tous-{{ $loop->index }}', -1)"><i class="bx bx-chevron-left"></i></button>
                    <span class="small text-muted page-info">Page 1 / {{ max(1, $totalPages) }}</span>
                    <button class="btn btn-sm btn-outline-secondary btn-next" onclick="changePage('table-tous-{{ $loop->index }}', 1)"><i class="bx bx-chevron-right"></i></button>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        @foreach($types as $typeKey => $typeLabel)
          @php
            $prixForType = $agent->prix->filter(fn($p) => ($p->type_transporteur ?? '') === $typeKey);
            $prixParProduitForType = $prixForType->groupBy('produit_id');
            $sectionId = 'section-' . Str::slug($typeKey);
            $hasPrix = $prixParProduitForType->isNotEmpty();
          @endphp

          <div class="card mb-4 prix-section" id="{{ $sectionId }}" data-type="{{ $typeKey }}" style="display: {{ $typeKey === 'Camion PGF' ? 'block' : 'none' }};">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix avec {{ $typeLabel }}</h5>
              <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix" onclick="setTypeTransporteur('{{ $typeKey }}')">
                <i class="bx bx-plus me-1"></i>Ajouter
              </button>
            </div>
            <div class="card-body p-3">
              @if($hasPrix)
                <p class="text-muted small mb-3"><i class="bx bx-info-circle me-1"></i>Cliquez sur un produit pour filtrer</p>

                {{-- Tags produits horizontaux --}}
                <div class="d-flex flex-wrap gap-2 mb-4">
                  @foreach($prixParProduitForType as $produitId => $prixList)
                    @php
                      $produit = $produits->firstWhere('id', $produitId);
                      $produitNom = $produit?->nom ?? 'Produit non défini';

                      // Couleurs selon produit
                      $produitColors = match(true) {
                        str_contains($produitNom, 'Palmier') => ['#22c55e', '#86efac', '#f0fdf4'],
                        str_contains($produitNom, 'Coton') => ['#eab308', '#fde047', '#fefce8'],
                        default => ['#3b82f6', '#93c5fd', '#eff6ff'],
                      };
                    @endphp

                    <button type="button"
                            class="btn btn-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2"
                            onclick="toggleProduitTable('table-produit-{{ $loop->parent->index }}-{{ $loop->index }}', this)"
                            style="background: {{ $produitColors[2] }}; border: 2px solid {{ $produitColors[0] }}; color: {{ $produitColors[0] }};">
                      <i class="bx bx-package"></i>
                      <span class="fw-bold">{{ $produitNom }}</span>
                      <span class="badge rounded-pill ms-1" style="background: {{ $produitColors[0] }}; color: white;">{{ $prixList->count() }}</span>
                    </button>
                  @endforeach
                </div>

                {{-- Tables des produits (cachées par défaut, sauf première) --}}
                @foreach($prixParProduitForType as $produitId => $prixList)
                  @php
                    $produit = $produits->firstWhere('id', $produitId);
                    $produitNom = $produit?->nom ?? 'Produit non défini';
                    $produitColors = match(true) {
                      str_contains($produitNom, 'Palmier') => ['#22c55e', '#86efac', '#f0fdf4'],
                      str_contains($produitNom, 'Coton') => ['#eab308', '#fde047', '#fefce8'],
                      default => ['#3b82f6', '#93c5fd', '#eff6ff'],
                    };
                  @endphp

                  @php
                    $nbPrixType = $prixList->count();
                    $totalPages = (int) ceil($nbPrixType / 10);
                  @endphp
                  <div id="table-produit-{{ $loop->parent->index }}-{{ $loop->index }}" class="produit-table mb-4" data-current-page="1" data-total-pages="{{ max(1, $totalPages) }}">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-top" style="background: {{ $produitColors[0] }};">
                      <span class="text-white fw-bold">
                        <i class="bx bx-list-ul me-2"></i>{{ $produitNom }} —
                        <span class="prix-count-visible">{{ $nbPrixType }}</span> / {{ $nbPrixType }} prix
                      </span>
                      <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix"
                              onclick="setTypeAndProduit('{{ $typeKey }}', {{ $produitId ?? 'null' }})">
                        <i class="bx bx-plus me-1"></i>Ajouter
                      </button>
                    </div>
                    @include('shared._prix_table_filtres')
                    <div class="alert alert-warning py-2 mb-0 mx-0 rounded-0 prix-filtre-empty d-none">
                      Aucun prix ne correspond à votre recherche.
                    </div>
                    <div class="table-responsive border border-top-0 rounded-bottom" style="max-height: 400px; overflow-y: auto;">
                      <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                          <tr>
                            <th class="ps-3">USINE</th>
                            <th class="text-end">PRIX</th>
                            <th>DÉBUT</th>
                            <th>FIN</th>
                            <th class="text-center pe-3">ACTIONS</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($prixList as $index => $prix)
                            <tr class="prix-row" data-row-index="{{ $index }}"
                                data-usine="{{ mb_strtolower($prix->nom_usine ?? '', 'UTF-8') }}"
                                data-date-debut="{{ $prix->date_debut ? $prix->date_debut->format('Y-m-d') : '' }}"
                                data-date-fin="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}">
                              <td class="ps-3"><strong>{{ $prix->nom_usine }}</strong></td>
                              <td class="text-end">
                                <span class="badge bg-success">{{ number_format($prix->prix, 0, ',', ' ') }} FCFA</span>
                              </td>
                              <td><small>{{ $prix->date_debut ? $prix->date_debut->format('d-m-Y') : '-' }}</small></td>
                              <td><small>{{ $prix->date_fin ? $prix->date_fin->format('d-m-Y') : '-' }}</small></td>
                              <td class="text-center pe-3">
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
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                    <div class="p-2 border border-top-0 rounded-bottom bg-light d-flex justify-content-center align-items-center gap-2 pagination-controls" style="{{ $totalPages > 1 ? '' : 'display:none;' }}">
                      <button class="btn btn-sm btn-outline-secondary btn-prev" onclick="changePage('table-produit-{{ $loop->parent->index }}-{{ $loop->index }}', -1)"><i class="bx bx-chevron-left"></i></button>
                      <span class="small text-muted page-info">Page 1 / {{ max(1, $totalPages) }}</span>
                      <button class="btn btn-sm btn-outline-secondary btn-next" onclick="changePage('table-produit-{{ $loop->parent->index }}-{{ $loop->index }}', 1)"><i class="bx bx-chevron-right"></i></button>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="text-center py-5">
                  <i class="bx bx-inbox text-muted" style="font-size: 48px;"></i>
                  <p class="text-muted mt-3 mb-2">Aucun prix configuré pour ce type de transporteur</p>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix" onclick="setTypeTransporteur('{{ $typeKey }}')">
                    <i class="bx bx-plus me-1"></i>Ajouter un prix pour {{ $typeLabel }}
                  </button>
                </div>
              @endif
            </div>
          </div>
        @endforeach

        @if($agent->prix->isEmpty())
          <div class="card">
            <div class="card-body text-center py-5">
              <i class="bx bx-money text-muted" style="font-size: 48px;"></i>
              <p class="text-muted mt-3">Aucun prix unitaire configuré</p>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddPrix">
                <i class="bx bx-plus me-1"></i>Ajouter un premier prix
              </button>
            </div>
          </div>
        @endif
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
            <label class="form-label">Produit</label>
            <select name="produit_id" class="form-select" id="selectProduitAdd" onchange="filterUsinesByProduit(this)">
              <option value="">-- Sélectionner un produit --</option>
              @foreach($produits as $prod)
                <option value="{{ $prod->id }}">{{ $prod->nom }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Usine <span class="text-danger">*</span></label>
            <select name="id_usine" class="form-select" required onchange="updateNomUsinePrix(this)" id="selectUsineAdd">
              <option value="">Sélectionner une usine</option>
              <option value="all" data-nom="TOUTES LES USINES">TOUTES LES USINES</option>
              @foreach($usines as $usine)
                <option value="{{ $usine['id_usine'] ?? '' }}" data-nom="{{ $usine['nom_usine'] ?? '' }}" data-produit="{{ $usine['produit_id'] ?? '' }}">{{ $usine['nom_usine'] ?? '' }}</option>
              @endforeach
            </select>
            <input type="hidden" name="nom_usine" value="">
            <input type="hidden" name="toutes_usines" value="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Type transporteur</label>
            <select name="type_transporteur" class="form-select" id="selectTypeAdd">
              <option value="">Tous les types</option>
              @foreach($codesTransporteurs as $code)
                <option value="{{ $code->nom }}">{{ $code->nom }}</option>
              @endforeach
            </select>
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
          <p class="text-muted small mb-0">Plusieurs prix sont possibles pour la même usine si les périodes ne se chevauchent pas.</p>
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
            <label class="form-label">Produit</label>
            <select name="produit_id" class="form-select">
              <option value="" @selected(!$prix->produit_id)>-- Sans produit --</option>
              @foreach($produits as $prod)
                <option value="{{ $prod->id }}" @selected($prix->produit_id === $prod->id)>{{ $prod->nom }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Type transporteur</label>
            <select name="type_transporteur" class="form-select">
              <option value="" @selected(!$prix->type_transporteur)>Tous les types</option>
              @foreach($codesTransporteurs as $code)
                <option value="{{ $code->nom }}" @selected($prix->type_transporteur === $code->nom)>{{ $code->nom }}</option>
              @endforeach
            </select>
          </div>
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

// Afficher seulement la section du transporteur cliqué
function showTransporteurSection(typeNom) {
  // Cacher toutes les sections
  document.querySelectorAll('.prix-section').forEach(function(section) {
    section.style.display = 'none';
  });

  // Afficher la section correspondante
  var sectionId = 'section-' + typeNom.toLowerCase().replace(/\s+/g, '-');
  var section = document.getElementById(sectionId);
  if (section) {
    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function setTypeTransporteur(type) {
  var select = document.getElementById('selectTypeAdd');
  select.value = type;
  document.getElementById('selectProduitAdd').value = '';

  // Figer le champ si un type est spécifié
  toggleTypeTransporteurLock(type);
}

function setTypeAndProduit(type, produitId) {
  var select = document.getElementById('selectTypeAdd');
  select.value = type;
  document.getElementById('selectProduitAdd').value = produitId || '';

  // Figer le champ si un type est spécifié
  toggleTypeTransporteurLock(type);
}

function toggleTypeTransporteurLock(type) {
  var select = document.getElementById('selectTypeAdd');
  var form = document.getElementById('formAddPrix');
  var existingHidden = form.querySelector('input[name="type_transporteur_hidden"]');

  if (type) {
    select.disabled = true;
    select.classList.add('bg-light');
    // Ajouter un champ hidden pour envoyer la valeur quand disabled
    if (!existingHidden) {
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'type_transporteur_hidden';
      hidden.value = type;
      form.appendChild(hidden);
    } else {
      existingHidden.value = type;
    }
  } else {
    select.disabled = false;
    select.classList.remove('bg-light');
    if (existingHidden) {
      existingHidden.remove();
    }
  }
}

// Réinitialiser le modal quand il se ferme
document.getElementById('modalAddPrix').addEventListener('hidden.bs.modal', function () {
  var select = document.getElementById('selectTypeAdd');
  var form = document.getElementById('formAddPrix');
  select.disabled = false;
  select.classList.remove('bg-light');
  var existingHidden = form.querySelector('input[name="type_transporteur_hidden"]');
  if (existingHidden) {
    existingHidden.remove();
  }
  document.getElementById('formAddPrix').reset();
});

// Filtrer les usines selon le produit selectionne
function filterUsinesByProduit(selectProduit) {
  var produitId = selectProduit.value;
  var produitNom = selectProduit.options[selectProduit.selectedIndex]?.text?.toLowerCase() || '';
  var usineSelect = document.getElementById('selectUsineAdd');
  var options = usineSelect.querySelectorAll('option');
  var isPalmier = produitNom.includes('palmier');

  options.forEach(function(option) {
    if (option.value === '' || option.value === 'all') {
      option.style.display = 'block';
    } else {
      var optionProduitId = option.getAttribute('data-produit');

      if (isPalmier) {
        // Palmier: afficher uniquement usines API (sans produit_id)
        option.style.display = !optionProduitId ? 'block' : 'none';
      } else {
        // Autre produit: afficher uniquement usines locales de CE produit
        option.style.display = optionProduitId === produitId ? 'block' : 'none';
      }
    }
  });

  // Reset la selection usine
  usineSelect.value = '';
}

@include('shared._prix_table_filter_script')

// Initialiser filtres, pagination et visibilité des tableaux produits
document.addEventListener('DOMContentLoaded', function() {
  var sections = document.querySelectorAll('.card-body');
  sections.forEach(function(section) {
    var tables = section.querySelectorAll('.produit-table');
    tables.forEach(function(table, index) {
      if (index > 0) {
        table.style.display = 'none';
      }
    });
  });

  initPrixTableFiltres();
});

// Afficher/masquer le tableau d'un produit
function toggleProduitTable(tableId, button) {
  var section = button.closest('.card-body');
  section.querySelectorAll('.produit-table').forEach(function(table) {
    table.style.display = 'none';
  });

  var selectedTable = document.getElementById(tableId);
  if (selectedTable) {
    selectedTable.style.display = 'block';
    rafraichirPaginationPrixTable(selectedTable);
    selectedTable.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}
</script>
@endsection
