@php
  $sectionId = 'section-' . Str::slug($codeNom);
  $groupesPrix = $prixParTypeSlug[$typeSlug] ?? [];
  $nbTotal = collect($groupesPrix)->sum(fn ($g) => count($g['prix']));
  $idAgent = (int) ($agent['id_agent'] ?? 0);
@endphp

<div class="card mb-4 prix-section" id="{{ $sectionId }}" data-type="{{ $typeSlug }}" style="display: {{ !empty($defaultVisible) ? 'block' : 'none' }};">
  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix avec {{ $codeNom }}</h5>
    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix" onclick="setTypeCamion('{{ $typeSlug }}')">
      <i class="bx bx-plus me-1"></i>Ajouter
    </button>
  </div>
  <div class="card-body p-3">
    @if($nbTotal > 0)
      <p class="text-muted small mb-3"><i class="bx bx-info-circle me-1"></i>Cliquez sur un produit pour filtrer</p>

      <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach($groupesPrix as $groupe)
          @if(count($groupe['prix']) === 0)
            @continue
          @endif
          @php
            $produitNom = $groupe['nom'];
            $produitColors = match(true) {
              str_contains($produitNom, 'Palmier') => ['#22c55e', '#86efac', '#f0fdf4'],
              str_contains($produitNom, 'Hévéa') || str_contains($produitNom, 'Hevea') => ['#3b82f6', '#93c5fd', '#eff6ff'],
              str_contains($produitNom, 'Cacao') => ['#eab308', '#fde047', '#fefce8'],
              default => ['#6366f1', '#a5b4fc', '#eef2ff'],
            };
            $produitIdAttr = $groupe['id'] === 'sans' ? '' : $groupe['id'];
          @endphp
          <button type="button"
                  class="btn btn-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2"
                  onclick="toggleProduitTable('table-{{ $sectionId }}-{{ $loop->index }}', this)"
                  style="background: {{ $produitColors[2] }}; border: 2px solid {{ $produitColors[0] }}; color: {{ $produitColors[0] }};">
            <i class="bx bx-package"></i>
            <span class="fw-bold">{{ $produitNom }}</span>
            <span class="badge rounded-pill ms-1" style="background: {{ $produitColors[0] }}; color: white;">{{ count($groupe['prix']) }}</span>
          </button>
        @endforeach
      </div>

      @php $firstTableShown = false; @endphp
      @foreach($groupesPrix as $groupe)
        @if(count($groupe['prix']) === 0)
          @continue
        @endif
        @php
          $produitNom = $groupe['nom'];
          $produitColors = match(true) {
            str_contains($produitNom, 'Palmier') => ['#22c55e', '#86efac', '#f0fdf4'],
            str_contains($produitNom, 'Hévéa') || str_contains($produitNom, 'Hevea') => ['#3b82f6', '#93c5fd', '#eff6ff'],
            str_contains($produitNom, 'Cacao') => ['#eab308', '#fde047', '#fefce8'],
            default => ['#6366f1', '#a5b4fc', '#eef2ff'],
          };
          $produitIdAttr = $groupe['id'] === 'sans' ? 'null' : $groupe['id'];
          $totalPages = (int) ceil(count($groupe['prix']) / 10);
          $showTable = !$firstTableShown;
          if ($showTable) {
            $firstTableShown = true;
          }
        @endphp
        <div id="table-{{ $sectionId }}-{{ $loop->index }}" class="produit-table mb-4" data-current-page="1" data-total-pages="{{ max(1, $totalPages) }}" style="{{ $showTable ? '' : 'display:none;' }}">
          <div class="d-flex justify-content-between align-items-center p-3 rounded-top" style="background: {{ $produitColors[0] }};">
            <span class="text-white fw-bold"><i class="bx bx-list-ul me-2"></i>{{ $produitNom }} — {{ count($groupe['prix']) }} prix</span>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix" onclick="setTypeAndProduit('{{ $typeSlug }}', {{ $produitIdAttr }})">
              <i class="bx bx-plus me-1"></i>Ajouter
            </button>
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
                @foreach($groupe['prix'] as $index => $prix)
                  <tr class="prix-row" data-row-index="{{ $index }}">
                    <td class="ps-3"><strong>{{ $prix->nom_usine }}</strong></td>
                    <td class="text-end"><span class="badge bg-success">{{ number_format($prix->prix, 0, ',', ' ') }} FCFA</span></td>
                    <td><small>{{ $prix->date_debut ? $prix->date_debut->format('d-m-Y') : '-' }}</small></td>
                    <td><small>{{ $prix->date_fin ? $prix->date_fin->format('d-m-Y') : '-' }}</small></td>
                    <td class="text-center pe-3">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPrix{{ $prix->id }}"><i class="bx bx-edit"></i></button>
                      <form method="POST" action="{{ route('agents.prix.delete', ['id_agent' => $idAgent, 'prix_id' => $prix->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce prix ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @if($totalPages > 1)
          <div class="p-2 border border-top-0 rounded-bottom bg-light d-flex justify-content-center align-items-center gap-2 pagination-controls">
            <button type="button" class="btn btn-sm btn-outline-secondary btn-prev" onclick="changePage('table-{{ $sectionId }}-{{ $loop->index }}', -1)"><i class="bx bx-chevron-left"></i></button>
            <span class="small text-muted page-info">Page 1 / {{ $totalPages }}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-next" onclick="changePage('table-{{ $sectionId }}-{{ $loop->index }}', 1)"><i class="bx bx-chevron-right"></i></button>
          </div>
          @endif
        </div>
      @endforeach
    @else
      <div class="text-center py-5">
        <i class="bx bx-inbox text-muted" style="font-size: 48px;"></i>
        <p class="text-muted mt-3 mb-2">Aucun prix configuré pour ce type de transporteur</p>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrix" onclick="setTypeCamion('{{ $typeSlug }}')">
          <i class="bx bx-plus me-1"></i>Ajouter un prix pour {{ $codeNom }}
        </button>
      </div>
    @endif
  </div>
</div>
