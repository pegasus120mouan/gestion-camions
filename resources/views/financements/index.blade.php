@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">

    <div class="card">
      @if(!empty($external_error))
        <div class="alert alert-danger m-3">{{ $external_error }}</div>
      @endif

      <div class="table-responsive">
        <table class="table table-hover" style="border-collapse: separate; border-spacing: 0;">
          <thead>
            <tr style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
              <th class="text-white text-uppercase py-3" style="border-radius: 0;">Agent</th>
              <th class="text-white text-uppercase text-center py-3">Nombre de financements</th>
              <th class="text-white text-uppercase text-center py-3">Montant initial</th>
              <th class="text-white text-uppercase text-center py-3">Déjà remboursé</th>
              <th class="text-white text-uppercase text-center py-3" style="border-radius: 0;">Solde financement</th>
            </tr>
          </thead>
          <tbody>
            @forelse($financements as $f)
              @php
                $nombreFinancements = (int)($f['nombre_financements'] ?? 0);
                $montantInitial = (float)($f['montant_initial'] ?? 0);
                $dejaRembourse = (float)($f['deja_rembourse'] ?? 0);
                $soldeFinancement = (float)($f['solde_financement'] ?? 0);
                
                // Couleur du badge selon le nombre de financements
                if ($nombreFinancements == 0) {
                  $badgeColor = '#6c757d';
                } elseif ($nombreFinancements == 1) {
                  $badgeColor = '#28a745';
                } else {
                  $badgeColor = '#007bff';
                }
                
                // Couleur du solde
                $soldeColor = $soldeFinancement > 0 ? '#28a745' : '#6c757d';
              @endphp
              <tr style="border-bottom: 1px solid #f0f0f0;">
                <td class="py-3">
                  <div class="d-flex align-items-center">
                    <i class="bx bx-user me-2 text-primary"></i>
                    <strong>{{ strtoupper($f['nom_agent'] ?? '-') }}</strong>
                  </div>
                </td>
                <td class="text-center py-3">
                  <span class="badge rounded-pill" style="background-color: {{ $badgeColor }}; min-width: 30px;">
                    {{ $nombreFinancements }}
                  </span>
                </td>
                <td class="text-center py-3">
                  <strong>{{ number_format($montantInitial, 0, ',', ' ') }} FCFA</strong>
                </td>
                <td class="text-center py-3">
                  <span class="text-success">{{ number_format($dejaRembourse, 0, ',', ' ') }} FCFA</span>
                </td>
                <td class="text-center py-3">
                  <strong style="color: {{ $soldeColor }};">{{ number_format($soldeFinancement, 0, ',', ' ') }} FCFA</strong>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4">Aucun agent trouvé</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(!empty($pagination))
      @php
        $currentPage = (int) ($pagination['current_page'] ?? 1);
        $lastPage = (int) ($pagination['last_page'] ?? 1);
        $total = (int) ($pagination['total'] ?? 0);
      @endphp
      <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Affichage de 1 à {{ count($financements) }} sur {{ $total }} agent(s)</small>
        @if($lastPage > 1)
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('financements.index', ['page' => $currentPage - 1, 'search' => request('search')]) }}">&lt;</a>
              </li>
              @for($i = 1; $i <= min($lastPage, 3); $i++)
                <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                  <a class="page-link" href="{{ route('financements.index', ['page' => $i, 'search' => request('search')]) }}">{{ $i }}</a>
                </li>
              @endfor
              @if($lastPage > 4)
                <li class="page-item disabled"><span class="page-link">...</span></li>
              @endif
              @if($lastPage > 3)
                <li class="page-item {{ $lastPage == $currentPage ? 'active' : '' }}">
                  <a class="page-link" href="{{ route('financements.index', ['page' => $lastPage, 'search' => request('search')]) }}">{{ $lastPage }}</a>
                </li>
              @endif
              <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('financements.index', ['page' => $currentPage + 1, 'search' => request('search')]) }}">&gt;</a>
              </li>
            </ul>
          </nav>
        @endif
      </div>
    @endif

  </div>
</div>
@endsection
