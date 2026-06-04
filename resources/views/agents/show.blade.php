@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('agents.index') }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour aux agents
        </a>
        <h4 class="mb-0">
          <i class="bx bx-user text-primary me-2"></i>
          {{ $agent['nom_complet'] ?? 'Agent' }}
        </h4>
        <small class="text-muted">N° Agent: {{ $agent['numero_agent'] ?? '-' }} | Contact: {{ $agent['contact'] ?? '-' }}</small>
      </div>
    </div>

    <div class="row">
      <!-- Informations de l'agent -->
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label text-muted">Numéro Agent</label>
              <p class="fw-bold mb-0">{{ $agent['numero_agent'] ?? '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Nom complet</label>
              <p class="fw-bold mb-0">{{ $agent['nom_complet'] ?? '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Contact</label>
              <p class="fw-bold mb-0">{{ $agent['contact'] ?? '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted">Chef d'équipe</label>
              <p class="fw-bold mb-0">
                @if(!empty($agent['chef_equipe']))
                  <span class="badge bg-label-primary">{{ $agent['chef_equipe']['nom_complet'] }}</span>
                @else
                  -
                @endif
              </p>
            </div>
            <div class="mb-0">
              <label class="form-label text-muted">Date d'ajout</label>
              <p class="fw-bold mb-0">
                @if(!empty($agent['date_ajout']))
                  {{ \Carbon\Carbon::parse($agent['date_ajout'])->format('d-m-Y') }}
                @else
                  -
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix avec Camion Pisteur</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrixTransporteur">
              <i class="bx bx-plus me-1"></i>Ajouter
            </button>
          </div>
          <div class="card-body pb-0">
            <p class="text-muted small mb-3"><i class="bx bx-package me-1"></i>Produits — développez un produit pour voir les prix par usine</p>
            @include('agents._prix_accordion_produit', [
              'accordionId' => 'accordionPrixTransporteur',
              'groupesPrix' => $prixTransporteurParProduit ?? [],
              'modalAddId' => 'modalAddPrixTransporteur',
              'editModalPrefix' => 'modalEditPrixTrans',
              'agent' => $agent,
            ])
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Prix avec Camion PGF</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrixPgf">
              <i class="bx bx-plus me-1"></i>Ajouter
            </button>
          </div>
          <div class="card-body pb-0">
            <p class="text-muted small mb-3"><i class="bx bx-package me-1"></i>Produits — prix par usine pour ce type de camion</p>
            @include('agents._prix_accordion_produit', [
              'accordionId' => 'accordionPrixPgf',
              'groupesPrix' => $prixPgfParProduit ?? [],
              'modalAddId' => 'modalAddPrixPgf',
              'editModalPrefix' => 'modalEditPrixPgf',
              'agent' => $agent,
            ])
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark"><i class="bx bx-money me-2"></i>Prix avec autre Camion</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPrixAutreCamion">
              <i class="bx bx-plus me-1"></i>Ajouter
            </button>
          </div>
          <div class="card-body pb-0">
            <p class="text-muted small mb-3"><i class="bx bx-package me-1"></i>Produits — prix par usine pour ce type de camion</p>
            @include('agents._prix_accordion_produit', [
              'accordionId' => 'accordionPrixAutre',
              'groupesPrix' => $prixAutreParProduit ?? [],
              'modalAddId' => 'modalAddPrixAutreCamion',
              'editModalPrefix' => 'modalEditPrixAutre',
              'agent' => $agent,
            ])
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal Ajouter Prix Transporteur -->
<div class="modal fade" id="modalAddPrixTransporteur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.store', ['id_agent' => $agent['id_agent']]) }}" class="agent-prix-add-form">
        @csrf
        <input type="hidden" name="type" value="transporteur">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Ajouter Prix Transporteur</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @include('agents._prix_form_fields', ['prixPlaceholder' => 'Ex: 50'])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Ajouter Prix PGF -->
<div class="modal fade" id="modalAddPrixPgf" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.store', ['id_agent' => $agent['id_agent']]) }}" class="agent-prix-add-form">
        @csrf
        <input type="hidden" name="type" value="pgf">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title text-white">Ajouter Prix PGF</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @include('agents._prix_form_fields', ['prixPlaceholder' => 'Ex: 30'])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Ajouter Prix autre Camion -->
<div class="modal fade" id="modalAddPrixAutreCamion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.store', ['id_agent' => $agent['id_agent']]) }}" class="agent-prix-add-form">
        @csrf
        <input type="hidden" name="type" value="autre_camion">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title text-dark">Ajouter Prix autre Camion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @include('agents._prix_form_fields', ['prixPlaceholder' => 'Ex: 40'])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals pour modifier les prix -->
@foreach($prixTransporteur ?? [] as $prix)
<div class="modal fade" id="modalEditPrixTrans{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.update', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Modifier Prix — {{ $prix->nom_produit ?? '?' }} / {{ $prix->nom_usine }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" value="{{ $prix->prix }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ $prix->date_debut ? $prix->date_debut->format('Y-m-d') : '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@foreach($prixAutreCamion ?? [] as $prix)
<div class="modal fade" id="modalEditPrixAutre{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.update', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title text-dark">Modifier Prix autre Camion — {{ $prix->nom_produit ?? '?' }} / {{ $prix->nom_usine }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" value="{{ $prix->prix }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ $prix->date_debut ? $prix->date_debut->format('Y-m-d') : '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@foreach($prixPgf ?? [] as $prix)
<div class="modal fade" id="modalEditPrixPgf{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.update', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title text-white">Modifier Prix PGF — {{ $prix->nom_produit ?? '?' }} / {{ $prix->nom_usine }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
            <input type="number" name="prix" class="form-control" required min="0" value="{{ $prix->prix }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date début</label>
              <input type="date" name="date_debut" class="form-control" value="{{ $prix->date_debut ? $prix->date_debut->format('Y-m-d') : '' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" class="form-control" value="{{ $prix->date_fin ? $prix->date_fin->format('Y-m-d') : '' }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<script>
var usinesParProduitAgent = {!! json_encode($usinesParProduit ?? []) !!};

function usinesAgentPourProduit(produitId) {
  if (!produitId) return [];
  return usinesParProduitAgent[produitId] || usinesParProduitAgent[String(produitId)] || [];
}

function remplirUsinesAgentPrix(form, produitId) {
  var usineSelect = form.querySelector('.agent-prix-usine');
  if (!usineSelect) return;

  var liste = usinesAgentPourProduit(produitId);
  usineSelect.innerHTML = '<option value="">-- Sélectionner une usine --</option>';
  usineSelect.innerHTML += '<option value="all" data-nom="TOUTES LES USINES DU PRODUIT">Toutes les usines du produit</option>';

  liste.forEach(function(u) {
    var opt = document.createElement('option');
    opt.value = u.id_usine;
    opt.textContent = u.code ? (u.nom + ' (' + u.code + ')') : u.nom;
    opt.dataset.nom = u.nom;
    usineSelect.appendChild(opt);
  });

  if (!produitId) {
    usineSelect.disabled = true;
    usineSelect.options[0].textContent = '-- Sélectionner d\'abord un produit --';
  } else {
    usineSelect.disabled = false;
    if (liste.length === 0) {
      usineSelect.options[0].textContent = '-- Aucune usine pour ce produit --';
    }
  }
}

function syncNomUsineAgent(form) {
  var usineSelect = form.querySelector('.agent-prix-usine');
  var nomInput = form.querySelector('input[name="nom_usine"]');
  var toutesInput = form.querySelector('input[name="toutes_usines"]');
  if (!usineSelect || !nomInput) return;

  var opt = usineSelect.options[usineSelect.selectedIndex];
  nomInput.value = opt && opt.dataset.nom ? opt.dataset.nom : '';
  if (toutesInput) {
    toutesInput.value = usineSelect.value === 'all' ? '1' : '0';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.btn-ajouter-prix-produit').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var produitId = this.dataset.produitId || '';
      var modalId = this.getAttribute('data-bs-target');
      if (!modalId) return;
      var modalEl = document.querySelector(modalId);
      if (!modalEl) return;
      var form = modalEl.querySelector('.agent-prix-add-form');
      if (!form) return;
      var produitSelect = form.querySelector('.agent-prix-produit');
      if (produitSelect && produitId) {
        produitSelect.value = produitId;
        remplirUsinesAgentPrix(form, produitId);
        syncNomUsineAgent(form);
      }
    });
  });

  document.querySelectorAll('.agent-prix-add-form').forEach(function(form) {
    var produitSelect = form.querySelector('.agent-prix-produit');
    var usineSelect = form.querySelector('.agent-prix-usine');

    if (produitSelect) {
      produitSelect.addEventListener('change', function() {
        remplirUsinesAgentPrix(form, this.value);
        syncNomUsineAgent(form);
      });
      remplirUsinesAgentPrix(form, produitSelect.value);
    }

    if (usineSelect) {
      usineSelect.addEventListener('change', function() {
        syncNomUsineAgent(form);
      });
    }

    form.addEventListener('submit', function() {
      syncNomUsineAgent(form);
    });
  });
});
</script>
@endsection
