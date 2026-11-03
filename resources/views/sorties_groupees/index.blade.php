@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div>
        <h4 class="mb-0">Fiches de sorties Groupés</h4>
        <small class="text-muted">Cliquez sur le code d’une fiche pour les transferts et l’historique</small>
      </div>
      <a href="{{ route('sorties_groupees.creer') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i>Nouvelle fiche
      </a>
    </div>

    @include('sorties_groupees._onglets', ['onglet' => 'fiches'])

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        @foreach($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('sorties_groupees.index') }}" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label mb-1">Recherche</label>
            <input type="text" name="q" class="form-control" placeholder="Code, véhicule, agent, produit..." value="{{ request('q') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Statut</label>
            <select name="statut" class="form-select">
              <option value="">Tous</option>
              <option value="en_cours" @selected(request('statut') === 'en_cours')>En cours</option>
              <option value="cloturee" @selected(request('statut') === 'cloturee')>Clôturée</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Du</label>
            <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Au</label>
            <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            @if(request()->hasAny(['q', 'statut', 'date_debut', 'date_fin']))
              <a href="{{ route('sorties_groupees.index') }}" class="btn btn-outline-secondary">Effacer</a>
            @endif
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Code fiche</th>
              <th>Date</th>
              <th>Véhicule</th>
              <th>Produit</th>
              <th>Agent</th>
              <th class="text-end">Poids</th>
              <th>Statut</th>
              <th class="text-end">Transferts</th>
              <th>Destination</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($fiches as $fiche)
              <tr>
                <td>
                  <a href="{{ route('sorties_groupees.show', $fiche->id) }}" class="fw-semibold text-primary">
                    {{ $fiche->numero_fiche }}
                  </a>
                </td>
                <td>{{ $fiche->date_chargement?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $fiche->matricule_vehicule ?: '—' }}</td>
                <td>{{ $fiche->nom_produit ?: '—' }}</td>
                <td>
                  {{ $fiche->nom_agent ?: '—' }}
                  @if($fiche->numero_agent)
                    <div class="small text-muted">{{ $fiche->numero_agent }}</div>
                  @endif
                </td>
                <td class="text-end">{{ number_format((float) $fiche->poids_pont, 0, ',', ' ') }} kg</td>
                <td>
                  @if($fiche->statut_groupe === 'cloturee')
                    <span class="badge bg-label-success">Clôturée</span>
                  @else
                    <span class="badge bg-label-warning">En cours</span>
                  @endif
                </td>
                <td class="text-end">{{ (int) ($fiche->etapes_transfert_groupe_count ?? 0) }}</td>
                <td>
                  @if($fiche->usine)
                    {{ $fiche->usine }}
                  @elseif($fiche->localisation_type === 'parc')
                    <span class="text-muted">Sur parc</span>
                  @else
                    <span class="text-muted">En transit</span>
                  @endif
                </td>
                <td class="text-end text-nowrap">
                  <a href="{{ route('sorties_groupees.show', $fiche->id) }}" class="btn btn-sm btn-outline-primary" title="Transferts & historique">
                    <i class="bx bx-transfer"></i>
                  </a>
                  <form method="POST" action="{{ route('sorties_groupees.destroy', $fiche->id) }}" class="d-inline"
                        onsubmit="return confirm('Supprimer la fiche {{ $fiche->numero_fiche }} ?\nLes mouvements de stock des ponts liés (crédits / débits) seront aussi annulés.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer la fiche et ses mouvements de stock">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-5">
                  Aucune fiche groupée pour le moment.
                  <div class="mt-2">
                    <a href="{{ route('sorties_groupees.creer') }}" class="btn btn-sm btn-primary">Créer une fiche</a>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($fiches, 'hasPages') && $fiches->hasPages())
        <div class="card-footer d-flex justify-content-center">
          {{ $fiches->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
