@php
    $fichesSortieStats = app(\App\Services\FichesSortieStatsService::class)->stats();
    $totalFiches = $fichesSortieStats['total'];
    $fichesEnAttente = $fichesSortieStats['en_attente'];
    $fichesDechargees = $fichesSortieStats['dechargees'];
@endphp

<div class="row mb-4">
  <div class="col-md-4">
    <div class="card text-white" style="background-color: #00bcd4;">
      <div class="card-body py-3">
        <h3 class="mb-1 text-white">{{ $totalFiches }}</h3>
        <small>Nombre fiches de sortie <strong>Total</strong></small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-white" style="background-color: #ff9800;">
      <div class="card-body py-3">
        <h3 class="mb-1 text-white">{{ $fichesEnAttente }}</h3>
        <small>Fiches en <strong>attente de déchargement</strong></small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-white" style="background-color: #4caf50;">
      <div class="card-body py-3">
        <h3 class="mb-1 text-white">{{ $fichesDechargees }}</h3>
        <small>Fiches <strong>déchargées</strong></small>
      </div>
    </div>
  </div>
</div>
