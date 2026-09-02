@php
  $t = $transfert ?? null;
  $selectedMatricule = old('matricule_vehicule', $t?->matricule_vehicule);
  $selectedVehiculeId = old('vehicule_id', $t?->vehicule_id);
  $selectedClientType = old('client_type', $t?->client_type ?: 'usine');
  $selectedClientId = old('client_id', $t?->client_id);
  $selectedClientName = old('client', $t?->client);
  $selectedProduitId = old('produit_id', $t?->produit_id);
  $selectedLieuDepart = old('lieu_depart', $t?->lieu_depart);
  $selectedLieuDestination = old('lieu_destination', $t?->lieu_destination);
@endphp
<div class="row g-3 js-transfert-form"
  data-client-type="{{ $selectedClientType }}"
  data-client-id="{{ $selectedClientId }}"
  data-client-name="{{ $selectedClientName }}"
  data-lieu-depart="{{ $selectedLieuDepart }}"
  data-lieu-destination="{{ $selectedLieuDestination }}"
>
  <div class="col-md-6">
    <label class="form-label">Date de chargement <span class="text-danger">*</span></label>
    <input
      type="date"
      name="date_chargement"
      class="form-control"
      required
      value="{{ old('date_chargement', $t?->date_chargement?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
    />
  </div>
  <div class="col-md-6">
    <label class="form-label">Véhicule <span class="text-danger">*</span></label>
    <select
      name="matricule_vehicule"
      class="form-select js-transfert-vehicule"
      data-placeholder="-- Choisir ou saisir --"
      required
    >
      <option value="">-- Choisir ou saisir --</option>
      @foreach($vehicules as $v)
        @php
          $selected = $selectedMatricule === $v['matricule']
            || (int) $selectedVehiculeId === (int) $v['vehicule_id'];
        @endphp
        <option
          value="{{ $v['matricule'] }}"
          data-vehicule-id="{{ $v['vehicule_id'] }}"
          @selected($selected)
        >{{ $v['matricule'] }}</option>
      @endforeach
      @if($selectedMatricule && !collect($vehicules)->contains(fn ($v) => $v['matricule'] === $selectedMatricule))
        <option value="{{ $selectedMatricule }}" selected>{{ $selectedMatricule }}</option>
      @endif
    </select>
    <input type="hidden" name="vehicule_id" class="js-transfert-vehicule-id" value="{{ $selectedVehiculeId }}" />
    <div class="form-text">Tapez pour rechercher ou saisir un matricule.</div>
  </div>

  <div class="col-md-12">
    <label class="form-label">Produit <span class="text-danger">*</span></label>
    <select name="produit_id" class="form-select js-transfert-produit" data-placeholder="-- Choisir un produit --" required>
      <option value="">-- Choisir un produit --</option>
      @foreach(($produits ?? []) as $produit)
        <option value="{{ $produit->id }}" @selected((string) $selectedProduitId === (string) $produit->id)>
          {{ $produit->nom }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-md-12">
    <label class="form-label d-block">Type de client <span class="text-danger">*</span></label>
    <div class="btn-group" role="group">
      <input type="radio" class="btn-check js-transfert-client-type" name="client_type" id="client_type_usine_{{ $t?->id ?? 'create' }}" value="usine" autocomplete="off" @checked($selectedClientType === 'usine')>
      <label class="btn btn-outline-primary" for="client_type_usine_{{ $t?->id ?? 'create' }}">
        <i class="bx bx-buildings me-1"></i>Usines
      </label>

      <input type="radio" class="btn-check js-transfert-client-type" name="client_type" id="client_type_particulier_{{ $t?->id ?? 'create' }}" value="particulier" autocomplete="off" @checked($selectedClientType === 'particulier')>
      <label class="btn btn-outline-primary" for="client_type_particulier_{{ $t?->id ?? 'create' }}">
        <i class="bx bx-user me-1"></i>Particuliers
      </label>
    </div>
  </div>

  <div class="col-md-12">
    <label class="form-label">Client <span class="text-danger">*</span></label>
    <select class="form-select js-transfert-client" data-placeholder="-- Choisir un client --" required>
      <option value="">-- Choisir un client --</option>
    </select>
    <input type="hidden" name="client_id" class="js-transfert-client-id" value="{{ $selectedClientId }}" />
    <input type="hidden" name="client" class="js-transfert-client-name" value="{{ $selectedClientName }}" />
    <div class="form-text js-transfert-client-help">Sélectionnez d'abord le type de client.</div>
  </div>

  <div class="col-md-6">
    <label class="form-label">Lieu de départ <span class="text-danger">*</span></label>
    <select name="lieu_depart" class="form-select js-transfert-lieu-depart" data-placeholder="-- Choisir un site --" required>
      <option value="">-- Choisir un site --</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Lieu de destination <span class="text-danger">*</span></label>
    <select name="lieu_destination" class="form-select js-transfert-lieu-destination" data-placeholder="-- Choisir un site --" required>
      <option value="">-- Choisir un site --</option>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Poids départ (kg)</label>
    <input type="number" name="poids_depart" class="form-control" min="0" step="0.01" value="{{ old('poids_depart', $t?->poids_depart) }}" />
  </div>
  <div class="col-md-6">
    <label class="form-label">Poids arrivée (kg)</label>
    <input type="number" name="poids_arrivee" class="form-control" min="0" step="0.01" value="{{ old('poids_arrivee', $t?->poids_arrivee) }}" />
  </div>
</div>
