@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Gestion financière /</span> Montant Pisteur
    </h4>

    @if(!empty($external_error))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $external_error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @include('gestion_financiere._filtres_montant_agent', [
      'actionRoute' => route('gestionfinanciere.montant_agent'),
      'filtres' => $filtres,
      'filtresActifs' => $filtresActifs,
      'produits' => $produits,
      'usines' => $usines,
      'showSearch' => true,
      'search' => $search,
      'agentNoms' => $agentNoms,
    ])

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-user-check me-2"></i>Liste des agents</h5>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th class="text-uppercase small text-muted">Agent</th>
              <th class="text-end text-uppercase small text-muted">
                Montant dû
                @if(!empty($filtresActifs))
                  <br><small class="fw-normal">(filtre)</small>
                @endif
              </th>
              <th class="text-end text-uppercase small text-muted">Montant payé</th>
              <th class="text-end text-uppercase small text-muted">Reste à payer</th>
              <th class="text-center text-uppercase small text-muted">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $item)
              @php
                $idAgent = $item['agent']['id_agent'] ?? 0;
                $nomComplet = $item['agent']['nom_complet'] ?? (($item['agent']['nom_agent'] ?? '') . ' ' . ($item['agent']['prenom_agent'] ?? ''));
                $numeroAgent = $item['agent']['numero_agent'] ?? '';
                $lienAgent = route('gestionfinanciere.agent.show', array_merge(['id_agent' => $idAgent], array_filter([
                  'produit_id' => $filtres['produit_id'] ?? null,
                  'usine' => $filtres['usine'] ?? null,
                  'date_debut' => $filtres['date_debut'] ?? null,
                  'date_fin' => $filtres['date_fin'] ?? null,
                ])));
              @endphp
              <tr>
                <td>
                  <a href="{{ $lienAgent }}" class="text-primary fw-bold">
                    {{ trim($nomComplet) ?: '—' }}
                  </a>
                  @if($numeroAgent !== '')
                    <br><small class="text-muted">{{ $numeroAgent }}</small>
                  @endif
                </td>
                <td class="text-end">
                  <span class="text-primary fw-bold">{{ number_format($item['montant_du'], 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-end">
                  <span class="text-success">{{ number_format($item['montant_paye'], 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-end">
                  @if($item['reste_a_payer'] > 0)
                    <span class="text-danger fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @elseif($item['reste_a_payer'] < 0)
                    <span class="text-warning fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                  @else
                    <span class="text-success"><i class="bx bx-check-circle"></i> Soldé</span>
                  @endif
                </td>
                <td class="text-center">
                  <a href="{{ $lienAgent }}" class="btn btn-sm btn-outline-success" title="Paiements via bordereaux">
                    <i class="bx bx-money"></i> Bordereaux
                  </a>
                  <a href="{{ route('agents.show', ['id_agent' => $idAgent]) }}" class="btn btn-sm btn-outline-primary" title="Fiche agent et tarifs">
                    <i class="bx bx-show"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  @if(!empty($search))
                    Aucun agent trouvé pour « {{ $search }} »
                  @else
                    Aucun agent à afficher
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
          @if(count($data) > 0)
          <tfoot class="table-light">
            <tr>
              <th class="text-end">TOTAUX</th>
              <th class="text-end text-primary fw-bold">{{ number_format(collect($data)->sum('montant_du'), 0, ',', ' ') }} FCFA</th>
              <th class="text-end text-success">{{ number_format(collect($data)->sum('montant_paye'), 0, ',', ' ') }} FCFA</th>
              <th class="text-end text-danger fw-bold">{{ number_format(collect($data)->sum('reste_a_payer'), 0, ',', ' ') }} FCFA</th>
              <th></th>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>

@include('gestion_financiere._filtres_montant_agent_js')
@endsection
