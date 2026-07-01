@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Camions PGF</h4>
      <a href="{{ route('camions.camions_pgf.ajouter') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i>Ajouter des camions
      </a>
    </div>

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des camions du groupe PGF</h5>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control" placeholder="Rechercher..." value="{{ request('q') }}" style="width: 200px;">
          <button type="submit" class="btn btn-outline-primary"><i class="bx bx-search"></i></button>
        </form>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Matricule</th>
              <th>Type</th>
              <th>Date d'ajout</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($camions_pgf as $index => $v)
              @php
                $vehiculeId = (int) ($v['vehicules_id'] ?? 0);
                $estEnCours = !empty($vehicules_en_cours[$vehiculeId]);
                $etatCamion = $estEnCours
                  ? 'en_cours'
                  : ($etats_par_vehicule[$vehiculeId] ?? 'actif');
                $estEnPanne = $etatCamion === 'en_panne' || $etatCamion === 'inactif';
              @endphp
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                  @if($estEnPanne)
                    <span class="text-muted"><strong>{{ $v['matricule_vehicule'] ?? '-' }}</strong></span>
                  @else
                    <a href="{{ route('vehicules.depenses', ['vehicule_id' => $v['vehicules_id'], 'matricule' => $v['matricule_vehicule']]) }}">
                      <strong>{{ $v['matricule_vehicule'] ?? '-' }}</strong>
                    </a>
                  @endif
                </td>
                <td>
                  @php $typeVehicule = strtolower($v['type_vehicule'] ?? ''); @endphp
                  @if($typeVehicule === 'voiture')
                    <i class="bx bxs-truck text-primary"></i> Camion
                  @elseif($typeVehicule === 'moto')
                    <i class="bx bx-cycling text-success"></i> Moto
                  @else
                    {{ $v['type_vehicule'] ?? '-' }}
                  @endif
                </td>
                <td>{{ $v['created_at'] ?? '-' }}</td>
                <td>
                  @if($etatCamion === 'en_cours')
                    <span class="badge bg-label-warning">En cours d'utilisation</span>
                  @elseif($etatCamion === 'actif')
                    <span class="badge bg-label-success">Actif</span>
                  @else
                    <span class="badge bg-label-danger">En panne</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('vehicules.etat.update', ['vehicule_id' => $vehiculeId]) }}" class="d-flex gap-1">
                      @csrf
                      <input type="hidden" name="matricule" value="{{ $v['matricule_vehicule'] ?? '' }}">
                      <select name="etat" class="form-select form-select-sm" style="min-width: 140px;" {{ $estEnCours ? 'disabled' : '' }}>
                        <option value="actif" {{ (($etats_par_vehicule[$vehiculeId] ?? 'actif') === 'actif') ? 'selected' : '' }}>Actif</option>
                        <option value="en_panne" {{ (($etats_par_vehicule[$vehiculeId] ?? 'actif') === 'en_panne') ? 'selected' : '' }}>En panne</option>
                      </select>
                      <button type="submit" class="btn btn-sm btn-outline-warning" {{ $estEnCours ? 'disabled' : '' }}>
                        Enregistrer
                      </button>
                    </form>

                    <form action="{{ route('camions.retirer_groupe', ['vehicule_id' => $v['vehicules_id']]) }}" method="POST" class="d-inline" onsubmit="return confirm('Retirer ce camion du groupe PGF ?');">
                      @csrf
                      @method('DELETE')
                      <input type="hidden" name="groupe_id" value="{{ $groupe_pgf->id }}">
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bx bx-trash"></i> Retirer
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">Aucun camion dans le groupe PGF</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Résumé</h5>
        <p><strong>Nombre de camions PGF:</strong> <span class="badge bg-primary">{{ count($camions_pgf) }}</span></p>
      </div>
    </div>
  </div>
</div>
@endsection
