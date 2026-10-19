@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb" class="mb-2">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="{{ route('acces.index') }}">Rôles</a></li>
        <li class="breadcrumb-item active">{{ $role['label'] }}</li>
      </ol>
    </nav>

    <form method="POST" action="{{ route('acces.update', $role['key']) }}" id="formAccesRole">
      @csrf
      @method('PUT')

      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
          <h4 class="mb-1">Accès : {{ $role['label'] }}</h4>
          <p class="text-muted mb-0">
            Cochez les modules accessibles pour le profil {{ $role['label'] }}
          </p>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('acces.index') }}" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </div>

      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="row g-4">
        @foreach($role['groupes'] as $groupe)
          <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm acces-groupe" data-groupe="{{ $groupe['key'] }}">
              <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold">{{ $groupe['label'] }}</h5>
                <button type="button" class="btn btn-sm btn-outline-primary btn-tout-selectionner">
                  Tout sélectionner
                </button>
              </div>
              <div class="card-body">
                @foreach($groupe['permissions'] as $perm)
                  <div class="form-check mb-2">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      name="permissions[]"
                      value="{{ $perm['key'] }}"
                      id="perm_{{ md5($perm['key']) }}"
                      @checked($perm['checked'])
                    >
                    <label class="form-check-label" for="perm_{{ md5($perm['key']) }}">
                      {{ $perm['label'] }}
                    </label>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('acces.index') }}" class="btn btn-outline-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.btn-tout-selectionner').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('.acces-groupe');
      if (!card) return;
      var boxes = card.querySelectorAll('input[type="checkbox"]');
      var allChecked = Array.prototype.every.call(boxes, function (b) { return b.checked; });
      boxes.forEach(function (b) { b.checked = !allChecked; });
      btn.textContent = allChecked ? 'Tout sélectionner' : 'Tout désélectionner';
    });
  });
});
</script>
@endsection
