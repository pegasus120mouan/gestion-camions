@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Détails de la pesée</h4>
      <div class="d-flex gap-2">
        <a href="{{ route('pesees.ticket', $pesee) }}" target="_blank" class="btn btn-outline-dark">Ticket PDF</a>
        <a href="{{ route('pesees.edit', $pesee) }}" class="btn btn-outline-primary">Modifier</a>
        <a href="{{ route('pesees.index') }}" class="btn btn-outline-secondary">Retour</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="text-muted">Date/heure</div>
            <div class="fw-semibold">{{ optional($pesee->pese_le)->format('d/m/Y H:i:s') }}</div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-muted">Référence</div>
            <div class="fw-semibold">{{ $pesee->reference }}</div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-muted">Pont</div>
            <div class="fw-semibold">{{ $pesee->pontPesage?->code }} - {{ $pesee->pontPesage?->nom }}</div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="text-muted">Camion</div>
            <div class="fw-semibold">{{ $pesee->camion?->immatriculation }}</div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-muted">Produit</div>
            <div class="fw-semibold">{{ $pesee->produit?->nom }}</div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-muted">Agent</div>
            <div class="fw-semibold">{{ $pesee->agent?->name }} {{ $pesee->agent?->prenom }}</div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="text-muted">Chauffeur</div>
            <div class="fw-semibold">{{ $pesee->chauffeur?->name }} {{ $pesee->chauffeur?->prenom }}</div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-muted">Notes</div>
            <div class="fw-semibold">{{ $pesee->notes }}</div>
          </div>
        </div>

        <hr />

        <div class="row">
          <div class="col-md-3 mb-3">
            <div class="text-muted">Poids brut</div>
            <div class="fw-semibold">{{ $pesee->poids_brut }}</div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="text-muted">Tare</div>
            <div class="fw-semibold">{{ $pesee->tare }}</div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="text-muted">Après réfraction</div>
            <div class="fw-semibold">{{ $pesee->poids_apres_refraction }}</div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="text-muted">Poids vide</div>
            <div class="fw-semibold">{{ $pesee->poids_vide }}</div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-3 mb-3">
            <div class="text-muted">Poids net</div>
            <div class="fw-semibold">{{ $pesee->poids_net }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
