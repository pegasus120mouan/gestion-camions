<div class="mb-3">
  <label class="form-label">Produit <span class="text-danger">*</span></label>
  <select name="produit_id" class="form-select agent-prix-produit" required>
    <option value="">-- Sélectionner un produit --</option>
    @foreach($produits ?? [] as $produit)
      <option value="{{ $produit->id }}">{{ $produit->nom }}</option>
    @endforeach
  </select>
</div>
<div class="mb-3">
  <label class="form-label">Usine <span class="text-danger">*</span></label>
  <select name="id_usine" class="form-select agent-prix-usine" required disabled>
    <option value="">-- Sélectionner d'abord un produit --</option>
    <option value="all" data-nom="TOUTES LES USINES DU PRODUIT">Toutes les usines du produit</option>
  </select>
  <input type="hidden" name="nom_usine" value="">
  <input type="hidden" name="toutes_usines" value="0">
</div>
<div class="mb-3">
  <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
  <input type="number" name="prix" class="form-control" required min="0" placeholder="{{ $prixPlaceholder ?? 'Ex: 50' }}">
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
