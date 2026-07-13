@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Solde chef d'équipe</h4>
        <p class="text-muted mb-0">Renseignez le token pour interroger l'API des soldes.</p>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('warning'))
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="row">
      <div class="col-lg-5 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-key me-2"></i>Token chef d'équipe</h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('solde_chef_equipe.token') }}">
              @csrf
              <div class="mb-3">
                <label class="form-label">Token <span class="text-danger">*</span></label>
                <input type="text" name="token" class="form-control @error('token') is-invalid @enderror"
                  value="{{ old('token', $token) }}" placeholder="Ex: BAEB3101" required maxlength="50" />
                @error('token')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Ce token est envoyé à l'API <code>solde_chef_equipe.php</code>.</small>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save me-1"></i> Enregistrer et consulter
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnRefreshSolde" @if(empty($token)) disabled @endif>
                  <i class="bx bx-refresh me-1"></i> Actualiser
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-7 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-wallet me-2"></i>Solde actuel</h5>
          </div>
          <div class="card-body">
            @if(!empty($apiError))
              <div class="alert alert-warning mb-0">
                <i class="bx bx-error-circle me-1"></i> {{ $apiError }}
              </div>
            @elseif(!empty($solde))
              @if(!empty($solde['nom']))
              <p class="text-muted mb-2">{{ trim(($solde['nom'] ?? '') . ' ' . ($solde['prenoms'] ?? '')) }}</p>
              @endif
              <p class="text-muted small mb-2">Reste à payer</p>
              <h2 class="mb-3 {{ ($solde['reste_a_payer'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                {{ number_format($solde['reste_a_payer'], 0, ',', ' ') }} FCFA
              </h2>
              <div class="row g-2">
                <div class="col-sm-6">
                  <div class="border rounded p-3 bg-light">
                    <div class="text-muted small">Particuliers</div>
                    <div class="fw-bold {{ ($solde['reste_particuliers'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                      {{ number_format($solde['reste_particuliers'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="border rounded p-3 bg-light">
                    <div class="text-muted small">Professionnels</div>
                    <div class="fw-bold {{ ($solde['reste_professionnels'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                      {{ number_format($solde['reste_professionnels'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                  </div>
                </div>
              </div>
            @else
              <p class="text-muted mb-0">Saisissez un token et cliquez sur « Enregistrer et consulter ».</p>
            @endif
            <div id="soldeApiLoader" class="text-center py-4 d-none">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div id="soldeApiError" class="alert alert-danger d-none mt-3"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var btnRefresh = document.getElementById('btnRefreshSolde');
  if (!btnRefresh) return;

  btnRefresh.addEventListener('click', function() {
    var tokenInput = document.querySelector('input[name="token"]');
    var token = tokenInput ? tokenInput.value.trim() : '';
    if (!token) return;

    var loader = document.getElementById('soldeApiLoader');
    var errorBox = document.getElementById('soldeApiError');
    if (loader) loader.classList.remove('d-none');
    if (errorBox) errorBox.classList.add('d-none');

    fetch('{{ route('api.solde_chef_equipe') }}?token=' + encodeURIComponent(token), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (loader) loader.classList.add('d-none');
        if (data.success) {
          window.location.reload();
        } else if (errorBox) {
          errorBox.textContent = data.error || 'Erreur API';
          errorBox.classList.remove('d-none');
        }
      })
      .catch(function() {
        if (loader) loader.classList.add('d-none');
        if (errorBox) {
          errorBox.textContent = 'Impossible de joindre l\'API.';
          errorBox.classList.remove('d-none');
        }
      });
  });
});
</script>
@endsection
