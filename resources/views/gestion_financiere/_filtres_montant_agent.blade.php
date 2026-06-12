@php
  $actionRoute = $actionRoute ?? route('gestionfinanciere.montant_agent');
  $filtres = $filtres ?? [];
@endphp
<div class="card mb-4">
  <div class="card-header">
    <h6 class="mb-0"><i class="bx bx-filter-alt me-2"></i>Filtres</h6>
  </div>
  <div class="card-body">
    <form method="GET" action="{{ $actionRoute }}" class="row g-3 align-items-end">
      @if(!empty($showSearch))
        <div class="col-md-3">
          <label class="form-label">Recherche agent</label>
          <input type="text" name="q" class="form-control" value="{{ $search ?? request('q') }}" placeholder="Nom ou n° agent..." list="agents_noms_list" autocomplete="off" />
        </div>
      @endif
      <div class="col-md-2">
        <label class="form-label">Produit</label>
        <select name="produit_id" id="filtre_produit_montant" class="form-select">
          <option value="">Tous</option>
          @foreach($produits ?? [] as $produit)
            <option value="{{ $produit->id }}" {{ (string) ($filtres['produit_id'] ?? '') === (string) $produit->id ? 'selected' : '' }}>
              {{ $produit->nom }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Usine</label>
        <select name="usine" id="filtre_usine_montant" class="form-select" data-usine-selectionnee="{{ $filtres['usine'] ?? '' }}">
          <option value="">Toutes</option>
          @foreach($usines ?? [] as $nomUsine)
            <option value="{{ $nomUsine }}" {{ ($filtres['usine'] ?? '') === $nomUsine ? 'selected' : '' }}>{{ $nomUsine }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Date début</label>
        <input type="date" name="date_debut" class="form-control" value="{{ $filtres['date_debut'] ?? '' }}" />
      </div>
      <div class="col-md-2">
        <label class="form-label">Date fin</label>
        <input type="date" name="date_fin" class="form-control" value="{{ $filtres['date_fin'] ?? '' }}" />
      </div>
      <div class="col-md-12 d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i>Filtrer</button>
        <a href="{{ $actionRoute }}" class="btn btn-outline-secondary">Réinitialiser</a>
        @if($showSyntheseLink ?? true)
        <a href="{{ route('gestionfinanciere.synthese_produit', request()->only(['produit_id', 'usine', 'date_debut', 'date_fin'])) }}" class="btn btn-outline-info">
          <i class="bx bx-pie-chart-alt me-1"></i>Synthèse par produit
        </a>
        @endif
      </div>
    </form>
    @if(!empty($filtresActifs))
      <p class="text-muted small mb-0 mt-2">
        <i class="bx bx-info-circle me-1"></i>Filtre actif sur les montants dus. Les paiements enregistrés restent calculés au niveau global de l’agent.
      </p>
    @endif
  </div>
</div>

@if(!empty($showSearch))
<datalist id="agents_noms_list">
  @foreach($agentNoms ?? [] as $nomAgent)
    <option value="{{ $nomAgent }}"></option>
  @endforeach
</datalist>
@endif
