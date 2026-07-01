@php
  $transporteur = $transporteur ?? null;
  $formKey = $transporteur ? 'edit_' . $transporteur->id : 'create';
  $isEdit = $transporteur !== null;
@endphp
<input type="hidden" name="_form" value="{{ $formKey }}">

@if($isEdit)
  <div class="mb-3">
    <label class="form-label">Code</label>
    <input type="text" class="form-control" value="{{ $transporteur->code }}" readonly disabled>
  </div>
@else
  <div class="mb-3">
    <label class="form-label">Code</label>
    <div class="form-control bg-light text-muted">
      {{ $prochainCode ?? 'TRP-001' }} <small class="text-muted">(généré automatiquement)</small>
    </div>
  </div>
@endif

<div class="mb-3">
  <label class="form-label">Nom <span class="text-danger">*</span></label>
  <input
    type="text"
    name="nom"
    class="form-control @error('nom') is-invalid @enderror"
    value="{{ old('nom', $transporteur->nom ?? '') }}"
    maxlength="100"
    required
  />
  @error('nom')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Prénoms <span class="text-danger">*</span></label>
  <input
    type="text"
    name="prenoms"
    class="form-control @error('prenoms') is-invalid @enderror"
    value="{{ old('prenoms', $transporteur->prenoms ?? '') }}"
    maxlength="150"
    required
  />
  @error('prenoms')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>
