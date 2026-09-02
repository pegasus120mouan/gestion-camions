@php
  $sites = $sites ?? collect();
  $ownerType = $ownerType ?? 'particulier';
  $ownerId = $ownerId ?? '';
  $openAddOnError = $errors->any() && old('owner_type') === $ownerType && (string) old('owner_id') === (string) $ownerId && old('_site_action') === 'create';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h5 class="mb-0"><i class="bx bx-map me-2 text-primary"></i>Sites</h5>
    <small class="text-muted">Enregistrez les sites de ce client</small>
  </div>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddSite">
    <i class="bx bx-plus me-1"></i> Enregistrer un site
  </button>
</div>

<div class="table-responsive">
  <table class="table table-hover mb-0">
    <thead>
      <tr>
        <th>Nom du site</th>
        <th>Adresse</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($sites as $site)
        <tr>
          <td><strong>{{ $site->nom }}</strong></td>
          <td>{{ $site->adresse ?: '-' }}</td>
          <td class="text-end">
            <div class="d-inline-flex gap-1">
              <button
                type="button"
                class="btn btn-sm btn-icon btn-outline-warning"
                data-bs-toggle="modal"
                data-bs-target="#modalEditSite{{ $site->id }}"
                title="Modifier"
              >
                <i class="bx bx-edit"></i>
              </button>
              <form
                method="POST"
                action="{{ route('clients.sites.destroy', $site) }}"
                class="d-inline"
                onsubmit="return confirm('Supprimer ce site ?')"
              >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Supprimer">
                  <i class="bx bx-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="3" class="text-center text-muted py-4">
            <i class="bx bx-map-alt d-block mb-2" style="font-size: 1.75rem; opacity: 0.6;"></i>
            Aucun site enregistré
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="modal fade" id="modalAddSite" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="bx bx-map-pin me-2"></i>Enregistrer un site</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('clients.sites.store') }}">
        @csrf
        <input type="hidden" name="owner_type" value="{{ $ownerType }}" />
        <input type="hidden" name="owner_id" value="{{ $ownerId }}" />
        <input type="hidden" name="_site_action" value="create" />
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom du site <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" required maxlength="255" value="{{ old('nom') }}" placeholder="Ex: Entrepôt principal" />
          </div>
          <div class="mb-0">
            <label class="form-label">Adresse</label>
            <input type="text" name="adresse" class="form-control" maxlength="255" value="{{ old('adresse') }}" />
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

@foreach($sites as $site)
  <div class="modal fade" id="modalEditSite{{ $site->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5 class="modal-title text-white"><i class="bx bx-edit me-2"></i>Modifier le site</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('clients.sites.update', $site) }}">
          @csrf
          @method('PUT')
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nom du site <span class="text-danger">*</span></label>
              <input type="text" name="nom" class="form-control" required maxlength="255" value="{{ old('nom', $site->nom) }}" />
            </div>
            <div class="mb-0">
              <label class="form-label">Adresse</label>
              <input type="text" name="adresse" class="form-control" maxlength="255" value="{{ old('adresse', $site->adresse) }}" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-warning text-white"><i class="bx bx-check me-1"></i>Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach

@if($openAddOnError)
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var modal = document.getElementById('modalAddSite');
      if (modal && window.bootstrap) {
        new bootstrap.Modal(modal).show();
      }
    });
  </script>
@endif
