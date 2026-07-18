@php
  $accordionId = $accordionId ?? 'accordionPrix';
  $groupesPrix = $groupesPrix ?? [];
  $typeCamion = $typeCamion ?? 'autre_camion';
  $modalAddId = $modalAddId ?? 'modalAddPrixTransporteur';
  $editModalPrefix = $editModalPrefix ?? 'modalEditPrixTrans';
  $idAgent = $agent['id_agent'] ?? 0;
@endphp

@php
  $firstOpenIndex = null;
  foreach ($groupesPrix as $idx => $g) {
    if (count($g['prix']) > 0 && $firstOpenIndex === null) {
      $firstOpenIndex = $idx;
    }
  }
@endphp

<div class="accordion accordion-flush" id="{{ $accordionId }}">
  @foreach($groupesPrix as $index => $groupe)
    @php
      $produitKey = $groupe['id'] === 'sans' ? 'sans' : (string) $groupe['id'];
      $collapseId = $accordionId . '-produit-' . $produitKey;
      $nbPrix = count($groupe['prix']);
      $produitIdAttr = $groupe['id'] === 'sans' ? '' : $groupe['id'];
      $isOpen = $firstOpenIndex !== null && $index === $firstOpenIndex;
    @endphp
    <div class="accordion-item">
      <h2 class="accordion-header" id="heading-{{ $collapseId }}">
        <button class="accordion-button {{ $isOpen ? '' : 'collapsed' }}" type="button"
          data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
          aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
          aria-controls="{{ $collapseId }}">
          <i class="bx bx-package me-2 text-primary"></i>
          <strong>{{ $groupe['nom'] }}</strong>
          <span class="badge bg-label-primary ms-2">{{ $nbPrix }} prix</span>
        </button>
      </h2>
      <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}"
        aria-labelledby="heading-{{ $collapseId }}" data-bs-parent="#{{ $accordionId }}">
        <div class="accordion-body p-0">
          <div class="d-flex justify-content-end p-2 border-bottom bg-light">
            <button type="button" class="btn btn-sm btn-outline-primary btn-ajouter-prix-produit"
              data-bs-toggle="modal" data-bs-target="#{{ $modalAddId }}"
              data-produit-id="{{ $produitIdAttr }}">
              <i class="bx bx-plus me-1"></i>Ajouter pour {{ $groupe['nom'] }}
            </button>
          </div>
          @if($nbPrix > 0)
            <div class="table-responsive">
              <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Usine</th>
                    <th class="text-end">Prix (FCFA)</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($groupe['prix'] as $prix)
                    <tr>
                      <td><strong>{{ $prix->nom_usine }}</strong></td>
                      <td class="text-end">{{ number_format($prix->prix, 0, ',', ' ') }}</td>
                      <td>{{ $prix->date_debut ? $prix->date_debut->format('d-m-Y') : '-' }}</td>
                      <td>{{ $prix->date_fin ? $prix->date_fin->format('d-m-Y') : '-' }}</td>
                      <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                          data-bs-target="#{{ $editModalPrefix }}{{ $prix->id }}">
                          <i class="bx bx-edit"></i>
                        </button>
                        <form method="POST" action="{{ route('agents.prix.delete', ['id_agent' => $idAgent, 'prix_id' => $prix->id]) }}"
                          class="d-inline" onsubmit="return confirm('Supprimer ce prix ?')">
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
          @else
            <p class="text-center text-muted py-4 mb-0">Aucun prix pour ce produit</p>
          @endif
        </div>
      </div>
    </div>
  @endforeach
</div>
