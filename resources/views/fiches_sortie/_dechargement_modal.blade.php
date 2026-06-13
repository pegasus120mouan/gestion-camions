@php
  $f = $fiche;
  $pontGerable = (bool) ($gerableParPont[$f->id_pont] ?? false);
  $stockFiche = $f->stock_id ? \App\Models\Stock::find($f->stock_id) : null;
  if (!$stockFiche && $f->parc_id && $f->produit_id) {
    $stockFiche = \App\Models\Stock::where('parc_id', $f->parc_id)
      ->where('produit_id', $f->produit_id)
      ->where('type', 'entree')
      ->where('statut', 'ouvert')
      ->first();
  }
  $stockDispoFiche = 0;
  if ($stockFiche) {
    $sortiesStock = \App\Models\FicheSortie::where('stock_id', $stockFiche->id)
      ->whereNotNull('date_dechargement')
      ->whereNotNull('poids_pont')
      ->when($f->date_dechargement, fn ($q) => $q->where('id', '!=', $f->id))
      ->sum('poids_pont');
    $stockDispoFiche = max(0, (float) $stockFiche->total_entrees - (float) $sortiesStock);
  }
  $parcsForPont = $parcsParPont[$f->id_pont] ?? collect();
@endphp

<div class="modal fade" id="modalDechargement{{ $f->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bx bx-package me-2"></i>Déchargement — {{ $f->matricule_vehicule }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('fiches_sortie.dechargement', ['fiche_id' => $f->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <p class="mb-1"><strong>Date de chargement :</strong> {{ $f->date_chargement ? $f->date_chargement->format('d/m/Y') : '—' }}</p>
            <p class="mb-1"><strong>Pont :</strong> {{ $f->nom_pont ?? '—' }}</p>
            <p class="mb-1"><strong>Agent :</strong> {{ $f->nom_agent ?? '—' }}</p>
            @if($f->nom_parc)
              <p class="mb-1"><strong>Parc :</strong> {{ $f->nom_parc }}</p>
            @endif
            @if($f->nom_produit)
              <p class="mb-1"><strong>Produit :</strong> {{ $f->nom_produit }}</p>
            @endif
            @if($pontGerable && $stockFiche)
              <p class="mb-0"><strong>Stock disponible :</strong>
                <span class="text-success fw-bold">{{ number_format($stockDispoFiche, 0, ',', ' ') }} kg</span>
              </p>
            @elseif(!$pontGerable)
              <p class="mb-0 text-muted"><small>Pont non gérable — le déchargement n'impacte pas le stock.</small></p>
            @endif
            @if($f->usine)
              <p class="mb-0 mt-2"><strong>Usine :</strong> {{ $f->usine }}</p>
            @endif
          </div>

          @if($pontGerable)
          <div class="mb-3">
            <label class="form-label">Parc <span class="text-danger">*</span></label>
            <select name="parc_id" class="form-select @error('parc_id') is-invalid @enderror" required>
              <option value="">-- Sélectionner un parc --</option>
              @foreach($parcsForPont as $parc)
                @php
                  $stockOuvertParc = \App\Models\Stock::where('parc_id', $parc->id)
                    ->where('type', 'entree')
                    ->where('statut', 'ouvert')
                    ->when($f->produit_id, fn ($q) => $q->where('produit_id', $f->produit_id))
                    ->first();
                  $selectedParc = (int) old('parc_id', $f->parc_id ?? 0) === (int) $parc->id;
                @endphp
                @if($stockOuvertParc)
                  <option value="{{ $parc->id }}" @selected($selectedParc)>{{ $parc->nom }}</option>
                @else
                  <option value="{{ $parc->id }}" disabled>{{ $parc->nom }} (pas de stock ouvert{{ $f->nom_produit ? ' pour ' . $f->nom_produit : '' }})</option>
                @endif
              @endforeach
            </select>
            @error('parc_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          @endif

          <hr>
          <div class="mb-3">
            <label class="form-label">Date de déchargement <span class="text-danger">*</span></label>
            <input type="date" name="date_dechargement" class="form-control @error('date_dechargement') is-invalid @enderror"
              value="{{ old('date_dechargement', $f->date_dechargement ? $f->date_dechargement->format('Y-m-d') : date('Y-m-d')) }}" required>
            @error('date_dechargement')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">N° ticket <span class="text-danger">*</span></label>
            <input type="text" name="numero_ticket" class="form-control @error('numero_ticket') is-invalid @enderror"
              value="{{ old('numero_ticket', $f->numero_ticket ?? '') }}" placeholder="Numéro du ticket" maxlength="100" required />
            @error('numero_ticket')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Poids (kg) <span class="text-danger">*</span></label>
            <input type="number" name="poids_pont" id="poids_pont_{{ $f->id }}" class="form-control @error('poids_pont') is-invalid @enderror"
              value="{{ old('poids_pont', $f->poids_pont ?? '') }}" placeholder="Poids en kg" min="0.01" step="0.01" required
              onchange="calculerMontantCamion({{ $f->id }})" oninput="calculerMontantCamion({{ $f->id }})">
            @error('poids_pont')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Prix unitaire (FCFA/kg)</label>
            <input type="number" name="prix_unitaire_camion" id="prix_unitaire_camion_{{ $f->id }}" class="form-control"
              value="{{ old('prix_unitaire_camion', $f->prix_unitaire_camion ?? '') }}" placeholder="Ex: 150" min="0" step="1"
              onchange="calculerMontantCamion({{ $f->id }})" oninput="calculerMontantCamion({{ $f->id }})">
          </div>
          <div class="mb-3">
            <label class="form-label">Montant camion</label>
            <div class="input-group">
              <input type="text" id="montant_camion_display_{{ $f->id }}" class="form-control fw-bold" style="background-color: #e9ecef;" readonly placeholder="0">
              <span class="input-group-text">FCFA</span>
            </div>
            <input type="hidden" name="montant_camion" id="montant_camion_{{ $f->id }}" value="{{ old('montant_camion', $f->montant_camion ?? 0) }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
