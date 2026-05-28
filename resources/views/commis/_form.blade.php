@php
  $isEdit = !empty($edit);
  $formSuffix = $commi->id ?? 'create';
@endphp

<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">Pont <span class="text-danger">*</span></label>
    <select name="id_pont" id="commis_pont_{{ $formSuffix }}" class="form-select @error('id_pont') is-invalid @enderror" required>
      <option value="">-- Sélectionner un pont --</option>
      @foreach($ponts as $pont)
        <option value="{{ $pont['id_pont'] ?? '' }}"
          data-gerant="{{ $pont['gerant'] ?? '' }}"
          @selected(old('id_pont', $commi->id_pont ?? '') == ($pont['id_pont'] ?? ''))>
          {{ $pont['nom_pont'] ?? '' }} ({{ $pont['code_pont'] ?? '' }})
        </option>
      @endforeach
    </select>
    @error('id_pont')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label">Avatar</label>
    <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*" />
    @if($commi)
      <small class="text-muted d-block mt-1">
        <img src="{{ $commi->avatar_url }}" alt="" width="32" height="32" class="rounded-circle me-1" style="object-fit: cover;" />
        Image actuelle
      </small>
    @endif
    @error('avatar')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="mb-3">
  <label class="form-label">Gérant du pont</label>
  <input type="text" id="commis_gerant_{{ $formSuffix }}" class="form-control" value="{{ $commi->gerant ?? '' }}" readonly />
  <small class="text-muted">Sélectionné automatiquement selon le pont.</small>
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">Nom <span class="text-danger">*</span></label>
    <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
      value="{{ old('nom', $commi->nom ?? '') }}" required />
    @error('nom')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label">Prénom <span class="text-danger">*</span></label>
    <input type="text" name="prenom" class="form-control @error('prenom') is-invalid @enderror"
      value="{{ old('prenom', $commi->prenom ?? '') }}" required />
    @error('prenom')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="mb-3">
  <label class="form-label">Contact</label>
  <input type="text" name="contact" class="form-control @error('contact') is-invalid @enderror"
    value="{{ old('contact', $commi->contact ?? '') }}" placeholder="Numéro de téléphone" />
  @error('contact')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">Code PIN (4 chiffres) @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
    <div class="input-group @error('code_pin') is-invalid @enderror">
      <input type="password" name="code_pin" id="code_pin_{{ $formSuffix }}"
        class="form-control @error('code_pin') is-invalid @enderror"
        maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="new-password"
        {{ $isEdit ? '' : 'required' }} placeholder="{{ $isEdit ? 'Laisser vide pour ne pas changer' : 'Ex: 1234' }}" />
      <button type="button" class="btn btn-outline-secondary" tabindex="-1"
        onclick="togglePassword('code_pin_{{ $formSuffix }}', 'eyeIconPin_{{ $formSuffix }}')" aria-label="Afficher le code PIN">
        <i class="bx bx-hide" id="eyeIconPin_{{ $formSuffix }}"></i>
      </button>
    </div>
    @error('code_pin')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label">Mot de passe @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
    <div class="input-group @error('password') is-invalid @enderror">
      <input type="password" name="password" id="password_{{ $formSuffix }}"
        class="form-control @error('password') is-invalid @enderror"
        autocomplete="new-password" {{ $isEdit ? '' : 'required' }}
        placeholder="{{ $isEdit ? 'Laisser vide pour ne pas changer' : '' }}" />
      <button type="button" class="btn btn-outline-secondary" tabindex="-1"
        onclick="togglePassword('password_{{ $formSuffix }}', 'eyeIconPwd_{{ $formSuffix }}')" aria-label="Afficher le mot de passe">
        <i class="bx bx-hide" id="eyeIconPwd_{{ $formSuffix }}"></i>
      </button>
    </div>
    @error('password')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="mb-3">
  <label class="form-label">Confirmer le mot de passe @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
  <div class="input-group">
    <input type="password" name="password_confirmation" id="password_confirmation_{{ $formSuffix }}"
      class="form-control" autocomplete="new-password"
      {{ $isEdit ? '' : 'required' }}
      placeholder="{{ $isEdit ? 'Uniquement si vous changez le mot de passe' : '' }}" />
    <button type="button" class="btn btn-outline-secondary" tabindex="-1"
      onclick="togglePassword('password_confirmation_{{ $formSuffix }}', 'eyeIconPwdConfirm_{{ $formSuffix }}')" aria-label="Afficher la confirmation">
      <i class="bx bx-hide" id="eyeIconPwdConfirm_{{ $formSuffix }}"></i>
    </button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var pontSelect = document.getElementById('commis_pont_{{ $formSuffix }}');
  var gerantInput = document.getElementById('commis_gerant_{{ $formSuffix }}');
  if (!pontSelect || !gerantInput) return;

  function syncGerant() {
    var opt = pontSelect.options[pontSelect.selectedIndex];
    gerantInput.value = (opt && opt.dataset && opt.dataset.gerant) ? opt.dataset.gerant : '';
  }

  pontSelect.addEventListener('change', syncGerant);
  syncGerant();
});
</script>
