@extends('layout.main')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <a href="{{ route('transporteurs.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux transporteurs
        </a>
        <h4 class="mb-1">
          <span class="badge bg-label-primary me-2">{{ $transporteur->code }}</span>
          {{ $transporteur->nom }} {{ $transporteur->prenoms }}
        </h4>
        <p class="text-muted mb-0">Camions rattachés à ce transporteur.</p>
      </div>
      <a href="{{ route('transporteurs.camions.ajouter', $transporteur) }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i>Ajouter des camions
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">
          Liste des camions
          <span class="badge bg-label-primary">{{ $transporteur->vehicules->count() }}</span>
        </h5>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Rechercher..." value="{{ $search }}" style="width: 200px;">
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bx bx-search"></i></button>
        </form>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Matricule</th>
              <th>Date d'ajout</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($camions as $index => $camion)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $camion->matricule_vehicule }}</strong></td>
                <td>{{ $camion->created_at?->format('d-m-Y H:i') ?? '—' }}</td>
                <td>
                  <form
                    method="POST"
                    action="{{ route('transporteurs.camions.retirer', ['transporteur' => $transporteur, 'vehicule_id' => $camion->vehicule_id]) }}"
                    class="d-inline"
                    onsubmit="return confirm('Retirer ce camion du transporteur ?');"
                  >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bx bx-trash"></i> Retirer
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                  Aucun camion associé.
                  <a href="{{ route('transporteurs.camions.ajouter', $transporteur) }}">Ajouter des camions</a>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
