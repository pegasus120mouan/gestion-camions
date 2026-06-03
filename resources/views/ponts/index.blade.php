@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Ponts de pesage</h4>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('ponts.index') }}" class="row g-3 align-items-end">
          <div class="col-md-9">
            <label class="form-label">Recherche par nom</label>
            <input type="text" name="q" id="pont_search_input" class="form-control" value="{{ $search ?? request('q') }}" placeholder="Ex: AGBOVILLE, BEDIE..." list="ponts_noms_list" autocomplete="off" />
            <datalist id="ponts_noms_list">
              @foreach($pontNoms ?? [] as $nomPont)
                <option value="{{ $nomPont }}"></option>
              @endforeach
            </datalist>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('ponts.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        @if(!empty($external_error))
          <div class="alert alert-danger m-3">{{ $external_error }}</div>
        @endif

        <table class="table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Stock disponible</th>
              <th class="text-end">Solde</th>
              <th>Gerant</th>
              <th>Cooperatif</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($ponts as $p)
              @php
                $etatPont = $p['etat_pont'] ?? 'actif';
                $idPont = (int) ($p['id_pont'] ?? 0);
              @endphp
              <tr>
                <td>{{ $p['code_pont'] ?? '' }}</td>
                <td>
                  <a href="{{ route('ponts.stock', ['id_pont' => $idPont]) }}" class="text-primary fw-bold text-decoration-none">
                    {{ $p['nom_pont'] ?? '' }}
                  </a>
                </td>
                <td>
                  <strong>{{ number_format((float)($p['stock_disponible'] ?? 0), 0, ',', ' ') }} kg</strong>
                </td>
                <td class="text-end">
                  @php $solde = $p['solde'] ?? 0; @endphp
                  @if($solde > 0)
                    <span class="fw-bold text-success">{{ number_format((float)$solde, 0, ',', ' ') }} FCFA</span>
                  @elseif($solde < 0)
                    <span class="fw-bold text-danger">{{ number_format((float)$solde, 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-muted">0 FCFA</span>
                  @endif
                </td>
                <td>{{ $p['gerant'] ?? '' }}</td>
                <td>{{ $p['cooperatif'] ?? '-' }}</td>
                <td>
                  @if($etatPont === 'actif')
                    <span class="badge bg-success">Actif</span>
                  @elseif($etatPont === 'inactif')
                    <span class="badge bg-warning text-dark">Inactif</span>
                  @else
                    <span class="badge bg-danger">Fermé</span>
                  @endif
                  <form method="POST" action="{{ route('ponts.etat.update', ['id_pont' => $idPont]) }}" class="d-inline-block ms-1">
                    @csrf
                    <input type="hidden" name="nom_pont" value="{{ $p['nom_pont'] ?? '' }}" />
                    <input type="hidden" name="code_pont" value="{{ $p['code_pont'] ?? '' }}" />
                    <select name="etat" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()" title="Changer le statut du pont">
                      <option value="actif" @selected($etatPont === 'actif')>Actif</option>
                      <option value="inactif" @selected($etatPont === 'inactif')>Inactif</option>
                      <option value="ferme" @selected($etatPont === 'ferme')>Fermé</option>
                    </select>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center">
                  @if(!empty($search))
                    Aucun pont trouvé pour « {{ $search }} »
                  @else
                    Aucun pont
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(count($ponts) > 0)
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">Résumé</h5>
          <p class="mb-0">
            <strong>Total ponts affichés :</strong> {{ count($ponts) }}
            @if(!empty($search))
              <span class="text-muted">(filtre : « {{ $search }} »)</span>
            @endif
          </p>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
