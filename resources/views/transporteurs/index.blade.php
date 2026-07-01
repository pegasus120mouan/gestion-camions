@extends('layout.main')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Gestion Transporteurs</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateTransporteur">
        <i class="bx bx-plus me-1"></i> Ajouter un transporteur
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('transporteurs.index') }}" class="row g-3 align-items-end">
          <div class="col-md-8">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Code, nom, prénoms..." />
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('transporteurs.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Prénoms</th>
              <th>Camions</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($transporteurs as $transporteur)
              <tr>
                <td>
                  <a href="{{ route('transporteurs.show', $transporteur) }}" class="badge bg-label-primary text-decoration-none">
                    {{ $transporteur->code }}
                  </a>
                </td>
                <td><strong>{{ $transporteur->nom }}</strong></td>
                <td>{{ $transporteur->prenoms }}</td>
                <td>
                  <span class="badge bg-label-secondary">{{ $transporteur->vehicules_count ?? 0 }}</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditTransporteur{{ $transporteur->id }}">
                      <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteTransporteur{{ $transporteur->id }}">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">Aucun transporteur enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $transporteurs->links() }}
    </div>
  </div>
</div>

<div class="modal fade" id="modalCreateTransporteur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white">Ajouter un transporteur</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('transporteurs.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          @include('transporteurs._form_fields', ['prochainCode' => $prochainCode ?? null])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach($transporteurs as $transporteur)
<div class="modal fade" id="modalEditTransporteur{{ $transporteur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier le transporteur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('transporteurs.update', $transporteur) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @include('transporteurs._form_fields', ['transporteur' => $transporteur])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteTransporteur{{ $transporteur->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Supprimer le transporteur <strong>{{ $transporteur->code }}</strong> — {{ $transporteur->nom }} {{ $transporteur->prenoms }} ?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form action="{{ route('transporteurs.destroy', $transporteur) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

@if($errors->any())
@php
  $reopenEditTransporteurId = old('_form') && str_starts_with((string) old('_form'), 'edit_')
    ? str_replace('edit_', '', (string) old('_form'))
    : null;
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
  @if(old('_form') === 'create')
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCreateTransporteur')).show();
  @elseif($reopenEditTransporteurId)
    var el = document.getElementById('modalEditTransporteur{{ $reopenEditTransporteurId }}');
    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
  @endif
});
</script>
@endif
@endsection
