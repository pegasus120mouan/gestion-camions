@php
  $isEdit = !empty($edit);
  $selectedGroupeId = old('particulier_groupe_id', $agent->particulier_groupe_id ?? ($groupeId ?? ''));
  $lockGroupe = !empty($lockGroupe);
@endphp

<div class="mb-3">
  <label class="form-label">Groupe <span class="text-danger">*</span></label>
  @if($lockGroupe && $selectedGroupeId)
    <input type="hidden" name="particulier_groupe_id" value="{{ $selectedGroupeId }}" />
    <input type="text" class="form-control" value="{{ $groupes->firstWhere('id', $selectedGroupeId)?->nom_groupe ?? '' }}" readonly />
  @else
    <select name="particulier_groupe_id" class="form-select @error('particulier_groupe_id') is-invalid @enderror" required>
      <option value="">-- Sélectionner un groupe --</option>
      @foreach($groupes as $groupe)
        <option value="{{ $groupe->id }}" @selected($selectedGroupeId == $groupe->id)>
          {{ $groupe->nom_groupe }}
        </option>
      @endforeach
    </select>
    @error('particulier_groupe_id')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  @endif
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">Nom <span class="text-danger">*</span></label>
    <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
      value="{{ old('nom', $agent->nom ?? '') }}" required />
    @error('nom')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label">Prénoms <span class="text-danger">*</span></label>
    <input type="text" name="prenoms" class="form-control @error('prenoms') is-invalid @enderror"
      value="{{ old('prenoms', $agent->prenoms ?? '') }}" required />
    @error('prenoms')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="mb-3">
  <label class="form-label">Contact</label>
  <input type="text" name="contact" class="form-control @error('contact') is-invalid @enderror"
    value="{{ old('contact', $agent->contact ?? '') }}" placeholder="Numéro de téléphone" />
  @error('contact')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>
