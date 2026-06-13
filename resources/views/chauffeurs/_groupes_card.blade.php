@php
  $totalChauffeurs = (int) ($totalChauffeurs ?? $groupes->sum('chauffeurs_count'));
  $groupeFilter = $groupeFilter ?? null;
  $search = $search ?? '';

  $groupeColors = function (string $nom): array {
    return match (true) {
      str_contains($nom, 'PGF') => ['#ff9500', '#ffb347', '#fff5e6'],
      str_contains($nom, 'Autre') => ['#6c757d', '#adb5bd', '#f8f9fa'],
      default => ['#696cff', '#8592ff', '#eef0ff'],
    };
  };

  $filterUrl = function (?int $groupeId = null) use ($search) {
    return route('chauffeurs.index', array_filter([
      'chauffeur_groupe_id' => $groupeId,
      'q' => $search !== '' ? $search : null,
    ]));
  };
@endphp

<div class="card mb-4 border-0 shadow-sm">
  <div class="card-header bg-gradient-info text-white" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);">
    <h5 class="mb-0 text-white"><i class="bx bx-group me-2"></i>Groupes Chauffeurs</h5>
  </div>
  <div class="card-body p-3">
    <div class="d-flex flex-column gap-3">
      @php $allColors = ['#696cff', '#8592ff', '#eef0ff']; @endphp
      <a href="{{ $filterUrl() }}" class="text-decoration-none">
        <div class="chauffeur-groupe-card d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm"
             style="background: {{ $allColors[2] }}; border-left: 5px solid {{ $allColors[0] }}; transition: all 0.3s ease; cursor: pointer; {{ !$groupeFilter ? 'box-shadow: 0 0 0 2px ' . $allColors[0] . ';' : '' }}"
             onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)';"
             onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='{{ !$groupeFilter ? '0 0 0 2px ' . $allColors[0] : '0 2px 4px rgba(0,0,0,0.1)' }}';">
          <div class="d-flex align-items-center">
            <div class="me-3 d-flex align-items-center justify-content-center"
                 style="width: 45px; height: 45px; background: linear-gradient(135deg, {{ $allColors[0] }} 0%, {{ $allColors[1] }} 100%); border-radius: 50%; box-shadow: 0 4px 10px {{ $allColors[0] }}40;">
              <i class="bx bx-user text-white fs-5"></i>
            </div>
            <div>
              <h6 class="mb-0 fw-bold" style="color: {{ $allColors[0] }};">Tous les chauffeurs</h6>
              <small class="text-muted">{{ $totalChauffeurs }} chauffeur{{ $totalChauffeurs > 1 ? 's' : '' }}</small>
            </div>
          </div>
          <i class="bx bx-chevron-right fs-4" style="color: {{ $allColors[0] }};"></i>
        </div>
      </a>

      @foreach($groupes as $groupe)
        @php
          $colors = $groupeColors($groupe->nom_groupe);
          $count = (int) $groupe->chauffeurs_count;
          $isActive = (string) $groupeFilter === (string) $groupe->id;
        @endphp
        <a href="{{ $filterUrl((int) $groupe->id) }}" class="text-decoration-none">
          <div class="chauffeur-groupe-card d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm"
               style="background: {{ $colors[2] }}; border-left: 5px solid {{ $colors[0] }}; transition: all 0.3s ease; cursor: pointer; {{ $isActive ? 'box-shadow: 0 0 0 2px ' . $colors[0] . ';' : '' }}"
               onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)';"
               onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='{{ $isActive ? '0 0 0 2px ' . $colors[0] : '0 2px 4px rgba(0,0,0,0.1)' }}';">
            <div class="d-flex align-items-center">
              <div class="me-3 d-flex align-items-center justify-content-center"
                   style="width: 45px; height: 45px; background: linear-gradient(135deg, {{ $colors[0] }} 0%, {{ $colors[1] }} 100%); border-radius: 50%; box-shadow: 0 4px 10px {{ $colors[0] }}40;">
                <i class="bx bx-user text-white fs-5"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold" style="color: {{ $colors[0] }};">{{ $groupe->nom_groupe }}</h6>
                <small class="text-muted">{{ $count }} chauffeur{{ $count > 1 ? 's' : '' }}</small>
              </div>
            </div>
            <i class="bx bx-chevron-right fs-4" style="color: {{ $colors[0] }};"></i>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</div>
