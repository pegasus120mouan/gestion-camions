@if(!empty($showSoldeChefBanner))
<div class="container-xxl flex-grow-1 container-p-y pb-0 pt-2">
  <div class="card border-primary mb-0">
    <div class="card-body py-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar flex-shrink-0">
            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-wallet"></i></span>
          </div>
          <div>
            <h6 class="mb-0">Solde actuel</h6>
            @if(!empty($soldeChef) && !empty($soldeChef['nom']))
              <small class="text-muted">{{ trim($soldeChef['nom'] . ' ' . ($soldeChef['prenoms'] ?? '')) }}</small>
            @elseif(empty($soldeChefToken))
              <small class="text-warning">Non configuré</small>
            @endif
          </div>
        </div>

        @if(!empty($soldeChef))
          <div class="text-end">
            <h4 class="mb-1 {{ ($soldeChef['reste_a_payer'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
              {{ number_format($soldeChef['reste_a_payer'], 0, ',', ' ') }} FCFA
            </h4>
            <div class="d-flex flex-wrap justify-content-end gap-3 small">
              <span>
                Particuliers :
                <strong class="{{ ($soldeChef['reste_particuliers'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                  {{ number_format($soldeChef['reste_particuliers'] ?? 0, 0, ',', ' ') }} FCFA
                </strong>
              </span>
              <span>
                Professionnels :
                <strong class="{{ ($soldeChef['reste_professionnels'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                  {{ number_format($soldeChef['reste_professionnels'] ?? 0, 0, ',', ' ') }} FCFA
                </strong>
              </span>
            </div>
          </div>
        @elseif(!empty($soldeChefToken))
          <span class="text-warning small"><i class="bx bx-error-circle me-1"></i>Solde indisponible</span>
        @else
          <span class="text-muted small">—</span>
        @endif
      </div>
    </div>
  </div>
</div>
@endif
