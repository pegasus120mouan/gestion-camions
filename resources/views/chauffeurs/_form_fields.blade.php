@php
  $chauffeur = $chauffeur ?? null;
  $suffix = $chauffeur ? 'edit_' . $chauffeur->id : 'create';
  $formKey = $suffix;
  $defaultGroupeId = $defaultGroupeId ?? null;
  $useOld = old('_form') === $formKey;

  $groupeId = $useOld
    ? (int) old('chauffeur_groupe_id', $chauffeur->chauffeur_groupe_id ?? $defaultGroupeId)
    : (int) ($chauffeur->chauffeur_groupe_id ?? $defaultGroupeId);

  $nom = $useOld ? old('nom', $chauffeur->nom ?? '') : ($chauffeur->nom ?? '');
  $prenoms = $useOld ? old('prenoms', $chauffeur->prenoms ?? '') : ($chauffeur->prenoms ?? '');
  $contact = $useOld ? old('contact', $chauffeur->contact ?? '') : ($chauffeur->contact ?? '');
  $matriculeVehicule = $useOld ? old('matricule_vehicule', $chauffeur->matricule_vehicule ?? '') : ($chauffeur->matricule_vehicule ?? '');
  $vehiculeId = $useOld ? old('vehicule_id', $chauffeur->vehicule_id ?? '') : ($chauffeur->vehicule_id ?? '');
  $salaire = $useOld
    ? old('salaire', $chauffeur->salaire ?? '')
    : ($chauffeur ? ($chauffeur->salaire ?? 0) : '');
@endphp
<input type="hidden" name="_form" value="{{ $formKey }}" />
<div class="mb-3">
  <label class="form-label">Groupe <span class="text-danger">*</span></label>
  <select name="chauffeur_groupe_id" class="form-select" required>
    @foreach($groupes ?? [] as $groupe)
      <option value="{{ $groupe->id }}"
        @selected((int) $groupeId === (int) $groupe->id)>
        {{ $groupe->nom_groupe }}
      </option>
    @endforeach
  </select>
</div>
<div class="mb-3">
  <label class="form-label">Nom <span class="text-danger">*</span></label>
  <input type="text" name="nom" class="form-control" value="{{ $nom }}" required />
</div>
<div class="mb-3">
  <label class="form-label">Prénoms <span class="text-danger">*</span></label>
  <input type="text" name="prenoms" class="form-control" value="{{ $prenoms }}" required />
</div>
<div class="mb-3">
  <label class="form-label">Contact</label>
  <input type="text" name="contact" class="form-control" value="{{ $contact }}" placeholder="Ex: 07 00 00 00 00" />
</div>
<div class="mb-3">
  <label class="form-label">Camion associé</label>
  <select name="matricule_vehicule" class="form-select chauffeur-vehicule-select" data-vehicule-id-target="chauffeur_vehicule_id_{{ $suffix }}" data-placeholder="Tapez le matricule...">
    <option value="">-- Aucun camion --</option>
    @foreach($vehiculeOptions as $vehicule)
      <option value="{{ $vehicule['matricule'] }}"
        data-vehicule-id="{{ $vehicule['id'] }}"
        @selected($matriculeVehicule === $vehicule['matricule'])>
        {{ $vehicule['matricule'] }}
      </option>
    @endforeach
  </select>
  <div class="form-text">Tapez le matricule pour filtrer la liste.</div>
  <input type="hidden" name="vehicule_id" id="chauffeur_vehicule_id_{{ $suffix }}" value="{{ $vehiculeId }}" />
</div>
<div class="mb-3">
  <label class="form-label">Salaire (FCFA)</label>
  <input type="number" name="salaire" class="form-control" min="0" step="1" value="{{ $salaire }}" />
</div>
