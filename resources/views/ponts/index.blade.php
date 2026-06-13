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
              <th>Gérant</th>
              <th>Stock disponible</th>
              <th class="text-end">Solde</th>
              <th>Statut</th>
              <th>Gérable</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($ponts as $p)
              @php
                $etatPont = $p['etat_pont'] ?? 'actif';
                $idPont = (int) ($p['id_pont'] ?? 0);
              @endphp
              <tr>
                <td>
                  <a href="#" class="text-decoration-none fw-semibold text-secondary"
                     data-bs-toggle="modal" data-bs-target="#modalInfoPont"
                     data-code="{{ $p['code_pont'] ?? '' }}"
                     data-nom="{{ $p['nom_pont'] ?? '' }}"
                     data-gerant="{{ $p['gerant'] ?? '-' }}"
                     data-cooperatif="{{ $p['cooperatif'] ?? '-' }}"
                     onclick="remplirModalPont(this)">
                    {{ $p['code_pont'] ?? '' }}
                  </a>
                </td>
                <td>
                  <a href="{{ route('ponts.stock', ['id_pont' => $idPont]) }}" class="text-primary fw-bold text-decoration-none">
                    {{ $p['nom_pont'] ?? '' }}
                  </a>
                </td>
                <td>{{ $p['gerant'] ?: '-' }}</td>
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
                <td>
                  <form method="POST" action="{{ route('ponts.toggle_gerable', ['id_pont' => $idPont]) }}">
                    @csrf
                    <input type="hidden" name="nom_pont" value="{{ $p['nom_pont'] ?? '' }}" />
                    <input type="hidden" name="code_pont" value="{{ $p['code_pont'] ?? '' }}" />
                    @if($p['gerable'] ?? false)
                      <button type="submit" class="btn btn-sm btn-success">Gérable</button>
                    @else
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Non gérable</button>
                    @endif
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

<div class="modal fade" id="modalInfoPont" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Informations du pont</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Code</dt>
          <dd class="col-sm-8" id="modalPontCode">-</dd>
          <dt class="col-sm-4">Nom</dt>
          <dd class="col-sm-8" id="modalPontNom">-</dd>
          <dt class="col-sm-4">Gérant</dt>
          <dd class="col-sm-8" id="modalPontGerant">-</dd>
          <dt class="col-sm-4">Coopératif</dt>
          <dd class="col-sm-8" id="modalPontCooperatif">-</dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<script>
function remplirModalPont(el) {
  document.getElementById('modalPontCode').textContent      = el.dataset.code      || '-';
  document.getElementById('modalPontNom').textContent       = el.dataset.nom       || '-';
  document.getElementById('modalPontGerant').textContent    = el.dataset.gerant    || '-';
  document.getElementById('modalPontCooperatif').textContent = el.dataset.cooperatif || '-';
}
</script>
@endsection
