@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('agents.index', array_filter(['hors_pgf' => !empty($horsPgf) ? 1 : null])) }}" class="text-muted mb-2 d-inline-block">
          <i class="bx bx-arrow-back me-1"></i> Retour à la liste
        </a>
        <h4 class="mb-0">
          <i class="bx bx-user text-primary me-2"></i>{{ $agent['nom_complet'] ?? 'Agent' }}
        </h4>
        <small class="text-muted">
          N° {{ $agent['numero_agent'] ?? '-' }}
          @if(!empty($agent['chef_equipe']['nom_complet']))
            | Chef d'équipe : {{ $agent['chef_equipe']['nom_complet'] }}
          @endif
          @if(!empty($agent['contact'])) | Contact : {{ $agent['contact'] }} @endif
        </small>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddPrix">
        <i class="bx bx-plus me-1"></i>Ajouter un prix
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row">
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-info-circle me-2"></i>Informations</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label text-muted">N° agent</label>
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
                @if(!empty($agent['chef_equipe']['nom_complet']))
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

        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-header bg-gradient-info text-white" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);">
            <h5 class="mb-0 text-white"><i class="bx bx-truck me-2"></i>Codes Transporteurs</h5>
          </div>
          <div class="card-body p-3">
            <div class="d-flex flex-column gap-3">
              @foreach($codesTransporteurs as $code)
                @php
                  $typeSlug = $typeParCodeNom[$code->nom] ?? 'autre_camion';
                  $countPrix = $prixCountsParType[$typeSlug] ?? 0;
                  $colors = match(true) {
                    str_contains($code->nom, 'PGF') => ['#ff9500', '#ffb347', '#fff5e6'],
                    default => ['#6c757d', '#adb5bd', '#f8f9fa'],
                  };
                @endphp
                <a href="javascript:void(0)" onclick="showTransporteurSection('section-{{ Str::slug($code->nom) }}')" class="text-decoration-none">
                  <div class="transporteur-card d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm"
                       style="background: {{ $colors[2] }}; border-left: 5px solid {{ $colors[0] }}; transition: all 0.3s ease; cursor: pointer;"
                       onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)';"
                       onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                    <div class="d-flex align-items-center">
                      <div class="icon-circle me-3 d-flex align-items-center justify-content-center"
                           style="width: 45px; height: 45px; background: linear-gradient(135deg, {{ $colors[0] }} 0%, {{ $colors[1] }} 100%); border-radius: 50%; box-shadow: 0 4px 10px {{ $colors[0] }}40;">
                        <i class="bx bx-truck text-white fs-5"></i>
                      </div>
                      <div>
                        <h6 class="mb-0 fw-bold" style="color: {{ $colors[0] }};">{{ $code->nom }}</h6>
                        <small class="text-muted">{{ $countPrix }} prix configuré{{ $countPrix > 1 ? 's' : '' }}</small>
                      </div>
                    </div>
                    <i class="bx bx-chevron-right fs-4" style="color: {{ $colors[0] }};"></i>
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8" id="prixContainer">
        @php
          $defaultCodeNom = null;
          $maxPrixCount = 0;
          foreach ($codesTransporteurs as $code) {
            $slug = $typeParCodeNom[$code->nom] ?? 'autre_camion';
            $count = $prixCountsParType[$slug] ?? 0;
            if ($count > $maxPrixCount) {
              $maxPrixCount = $count;
              $defaultCodeNom = $code->nom;
            }
          }
          if (!$defaultCodeNom && $codesTransporteurs->isNotEmpty()) {
            $defaultCodeNom = $codesTransporteurs->first()->nom;
          }
        @endphp
        @foreach($codesTransporteurs as $code)
          @php
            $typeSlug = $typeParCodeNom[$code->nom] ?? 'autre_camion';
          @endphp
          @include('agents._prix_show_type_section', [
            'codeNom' => $code->nom,
            'typeSlug' => $typeSlug,
            'defaultVisible' => $code->nom === $defaultCodeNom,
          ])
        @endforeach

        @if($prixAll->isEmpty())
          <div class="card">
            <div class="card-body text-center py-5">
              <i class="bx bx-money text-muted" style="font-size: 48px;"></i>
              <p class="text-muted mt-3">Aucun prix unitaire configuré</p>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddPrix">
                <i class="bx bx-plus me-1"></i>Ajouter un premier prix
              </button>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAddPrix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.store', ['id_agent' => $agent['id_agent']]) }}" id="formAddPrix" class="agent-prix-add-form">
        @csrf
        <input type="hidden" name="type" id="inputTypeAdd" value="pgf">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Ajouter un prix unitaire</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @include('agents._prix_form_fields', ['prixPlaceholder' => 'Ex: 150'])
          <p class="text-muted small mb-0 mt-2">Plusieurs prix sont possibles pour la même usine si les périodes ne se chevauchent pas.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach($prixAll as $prix)
<div class="modal fade" id="modalEditPrix{{ $prix->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('agents.prix.update', ['id_agent' => $agent['id_agent'], 'prix_id' => $prix->id]) }}">
        @csrf
        @method('PUT')
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Modifier — {{ $prix->nom_produit ?? '?' }} / {{ $prix->nom_usine }}</h5>
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

<script>
var usinesParProduitAgent = {!! json_encode($usinesParProduit ?? []) !!};
var typeParCodeNom = {!! json_encode($typeParCodeNom ?? []) !!};

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

function showTransporteurSection(sectionId) {
  document.querySelectorAll('.prix-section').forEach(function(section) {
    section.style.display = 'none';
  });

  var section = document.getElementById(sectionId);
  if (section) {
    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function setTypeCamion(typeSlug) {
  document.getElementById('inputTypeAdd').value = typeSlug;
  var form = document.getElementById('formAddPrix');
  var produitSelect = form.querySelector('.agent-prix-produit');
  if (produitSelect) {
    produitSelect.value = '';
    remplirUsinesAgentPrix(form, '');
  }
}

function setTypeAndProduit(typeSlug, produitId) {
  document.getElementById('inputTypeAdd').value = typeSlug;
  var form = document.getElementById('formAddPrix');
  var produitSelect = form.querySelector('.agent-prix-produit');
  if (produitSelect) {
    produitSelect.value = produitId || '';
    remplirUsinesAgentPrix(form, produitId || '');
    syncNomUsineAgent(form);
  }
}

function toggleProduitTable(tableId, button) {
  var section = button.closest('.card-body');
  section.querySelectorAll('.produit-table').forEach(function(table) {
    table.style.display = 'none';
  });

  var selectedTable = document.getElementById(tableId);
  if (selectedTable) {
    selectedTable.style.display = 'block';
    rafraichirPaginationPrixTable(selectedTable);
    selectedTable.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

@include('shared._prix_table_filter_script')

document.addEventListener('DOMContentLoaded', function() {
  initPrixTableFiltres();

  var form = document.getElementById('formAddPrix');
  if (!form) return;

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

  document.getElementById('modalAddPrix').addEventListener('hidden.bs.modal', function() {
    form.reset();
    document.getElementById('inputTypeAdd').value = 'pgf';
    remplirUsinesAgentPrix(form, '');
  });
});
</script>
@endsection
