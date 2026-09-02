@extends('layout.main')

@section('page-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
  .modal .select2-container { z-index: 1056; }
  .transferts-table {
    font-size: 0.78rem;
    width: auto;
    min-width: 100%;
    table-layout: auto;
  }
  .transferts-table thead th {
    font-size: 0.72rem;
    letter-spacing: 0.02em;
    white-space: nowrap;
    padding: 0.4rem 0.45rem;
  }
  .transferts-table td {
    vertical-align: middle;
    padding: 0.35rem 0.45rem;
    white-space: nowrap;
  }
  .transferts-table .btn-sm {
    font-size: 0.7rem;
    padding: 0.2rem 0.45rem;
  }
  .transferts-table .btn-icon {
    width: 1.65rem;
    height: 1.65rem;
    padding: 0;
  }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y pt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-1">Liste des transferts</h4>
        <p class="text-muted mb-0">Enregistrement des transferts (chargement, véhicule, client, lieux, poids)</p>
      </div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateTransfert">
        <i class="bx bx-plus me-1"></i> Enregistrer un transfert
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

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('transferts.index') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Véhicule, produit, client, lieu..." />
          </div>
          <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" name="date_debut" class="form-control" value="{{ $dateDebut }}" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="{{ $dateFin }}" />
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="{{ route('transferts.index') }}" class="btn btn-outline-secondary">Réinit.</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table transferts-table">
          <thead>
            <tr>
              <th>Date chargement</th>
              <th>Véhicule</th>
              <th>Produit</th>
              <th>Client</th>
              <th>Départ</th>
              <th>Destination</th>
              <th class="text-end">Poids départ</th>
              <th class="text-end">Poids arrivée</th>
              <th class="text-end">Prix unitaire</th>
              <th class="text-end">Montant</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($transferts as $transfert)
              <tr>
                <td>{{ $transfert->date_chargement?->format('d/m/Y') }}</td>
                <td><strong>{{ $transfert->matricule_vehicule }}</strong></td>
                <td>{{ $transfert->nom_produit ?: '—' }}</td>
                <td>{{ $transfert->client }}</td>
                <td>{{ $transfert->lieu_depart }}</td>
                <td>{{ $transfert->lieu_destination }}</td>
                <td class="text-end">
                  {{ $transfert->poids_depart !== null ? number_format((float) $transfert->poids_depart, 0, ',', ' ') : '—' }}
                </td>
                <td class="text-end">
                  {{ $transfert->poids_arrivee !== null ? number_format((float) $transfert->poids_arrivee, 0, ',', ' ') : '—' }}
                </td>
                <td class="text-end">
                  @if($transfert->prix_unitaire !== null)
                    {{ number_format((float) $transfert->prix_unitaire, 0, ',', ' ') }}
                  @else
                    —
                  @endif
                </td>
                <td class="text-end text-danger fw-semibold">
                  @if($transfert->montant !== null)
                    {{ number_format((float) $transfert->montant, 0, ',', ' ') }} FCFA
                  @else
                    —
                  @endif
                </td>
                <td>
                  @if(($transfert->statut ?? 'non_decharge') === 'decharge')
                    <button type="button" class="btn btn-sm btn-secondary" disabled>
                      Déchargé
                    </button>
                  @else
                    <button
                      type="button"
                      class="btn btn-sm btn-warning js-open-decharge-modal"
                      data-bs-toggle="modal"
                      data-bs-target="#modalDechargerTransfert"
                      data-action="{{ route('transferts.decharger', $transfert) }}"
                      data-vehicule="{{ $transfert->matricule_vehicule }}"
                      data-client="{{ $transfert->client }}"
                      data-depart="{{ $transfert->lieu_depart }}"
                      data-destination="{{ $transfert->lieu_destination }}"
                      data-date="{{ $transfert->date_chargement?->format('d/m/Y') }}"
                    >
                      Non déchargé
                    </button>
                  @endif
                </td>
                <td>
                  @php $estPaye = ($transfert->paiement ?? 'non_paye') === 'paye'; @endphp
                  <div class="d-flex gap-1">
                    <button
                      type="button"
                      class="btn btn-sm {{ $estPaye ? 'btn-secondary' : 'btn-outline-success' }}"
                      title="{{ $estPaye ? 'Actions verrouillées (payé via bordereau)' : 'Prix unitaire' }}"
                      @if($estPaye) disabled @else
                        data-bs-toggle="modal"
                        data-bs-target="#modalPrixUnitaire"
                        data-action="{{ route('transferts.prix_unitaire', $transfert) }}"
                        data-vehicule="{{ $transfert->matricule_vehicule }}"
                        data-client="{{ $transfert->client }}"
                        data-poids="{{ $transfert->poids_arrivee ?? $transfert->poids_depart }}"
                        data-prix="{{ $transfert->prix_unitaire }}"
                      @endif
                    >
                      <i class="bx bx-money"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm {{ $estPaye ? 'btn-secondary' : 'btn-outline-primary' }}"
                      title="{{ $estPaye ? 'Actions verrouillées (payé via bordereau)' : 'Modifier' }}"
                      @if($estPaye) disabled @else
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditTransfert{{ $transfert->id }}"
                      @endif
                    >
                      <i class="bx bx-edit"></i>
                    </button>
                    @if($estPaye)
                      <button type="button" class="btn btn-sm btn-secondary" title="Actions verrouillées (payé via bordereau)" disabled>
                        <i class="bx bx-trash"></i>
                      </button>
                    @else
                      <form method="POST" action="{{ route('transferts.destroy', $transfert) }}" class="d-inline" onsubmit="return confirm('Supprimer ce transfert ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                          <i class="bx bx-trash"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="12" class="text-center py-4 text-muted">Aucun transfert enregistré</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $transferts->links() }}
    </div>
  </div>
</div>

{{-- Modal prix unitaire --}}
<div class="modal fade" id="modalPrixUnitaire" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success">
        <h5 class="modal-title text-white">
          <i class="bx bx-money me-2"></i>Renseigner le prix unitaire
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formPrixUnitaire" action="#">
        @csrf
        <div class="modal-body">
          <div class="rounded border bg-light p-3 mb-3">
            <div class="row g-2 small">
              <div class="col-5 text-muted">Véhicule</div>
              <div class="col-7 fw-semibold" id="prixModalVehicule">—</div>
              <div class="col-5 text-muted">Client</div>
              <div class="col-7 fw-semibold" id="prixModalClient">—</div>
              <div class="col-5 text-muted">Poids</div>
              <div class="col-7 fw-semibold"><span id="prixModalPoids">—</span> kg</div>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label" for="prixUnitaireInput">Prix unitaire (FCFA) <span class="text-danger">*</span></label>
            <input
              type="number"
              name="prix_unitaire"
              id="prixUnitaireInput"
              class="form-control form-control-lg"
              min="0"
              step="1"
              required
              placeholder="Ex: 80"
            />
            <div class="form-text">Le montant sera calculé automatiquement (prix × poids).</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">
            <i class="bx bx-save me-1"></i>Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal confirmation déchargement --}}
<div class="modal fade" id="modalDechargerTransfert" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-dark">
          <i class="bx bx-package me-2"></i>Confirmer le déchargement
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formDechargerTransfert" action="#">
        @csrf
        <div class="modal-body">
          <p class="mb-3">
            Voulez-vous marquer ce transfert comme <strong>déchargé</strong>&nbsp;?
          </p>
          <div class="rounded border bg-light p-3">
            <div class="row g-2 small">
              <div class="col-5 text-muted">Date</div>
              <div class="col-7 fw-semibold" id="dechargeModalDate">—</div>
              <div class="col-5 text-muted">Véhicule</div>
              <div class="col-7 fw-semibold" id="dechargeModalVehicule">—</div>
              <div class="col-5 text-muted">Client</div>
              <div class="col-7 fw-semibold" id="dechargeModalClient">—</div>
              <div class="col-5 text-muted">Trajet</div>
              <div class="col-7 fw-semibold">
                <span id="dechargeModalDepart">—</span>
                <i class="bx bx-right-arrow-alt mx-1 text-muted"></i>
                <span id="dechargeModalDestination">—</span>
              </div>
            </div>
          </div>
          <div class="alert alert-warning border-0 mt-3 mb-0 py-2 small">
            <i class="bx bx-info-circle me-1"></i>
            Cette action est définitive : le statut passera à <strong>Déchargé</strong>.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">
            <i class="bx bx-check me-1"></i>Confirmer le déchargement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal création --}}
<div class="modal fade" id="modalCreateTransfert" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-plus me-2"></i>Enregistrer un transfert</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('transferts.store') }}">
        @csrf
        <div class="modal-body">
          @include('transferts._form_fields', ['transfert' => null, 'vehicules' => $vehicules, 'produits' => $produits])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modals édition --}}
@foreach($transferts as $transfert)
<div class="modal fade" id="modalEditTransfert{{ $transfert->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-edit me-2"></i>Modifier le transfert</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('transferts.update', $transfert) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @include('transferts._form_fields', ['transfert' => $transfert, 'vehicules' => $vehicules, 'produits' => $produits])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@if($errors->any())
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var modal = new bootstrap.Modal(document.getElementById('modalCreateTransfert'));
    modal.show();
  });
</script>
@endif
@endsection

@section('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var clientsOptions = @json($clientsOptions ?? ['usine' => [], 'particulier' => []]);

  function syncVehiculeId($select) {
    var $wrap = $select.closest('.col-md-6');
    var $hidden = $wrap.find('.js-transfert-vehicule-id');
    var $opt = $select.find(':selected');
    $hidden.val($opt.attr('data-vehicule-id') || '');
  }

  function destroySelect2($sel) {
    if ($sel.hasClass('select2-hidden-accessible')) {
      $sel.select2('destroy');
    }
  }

  function initSelect2($sel, $modal, extra) {
    destroySelect2($sel);
    $sel.select2($.extend({
      theme: 'bootstrap-5',
      dropdownParent: $modal.find('.modal-content'),
      placeholder: $sel.data('placeholder') || '-- Choisir --',
      allowClear: true,
      width: '100%'
    }, extra || {}));
  }

  function getClientType($form) {
    return $form.find('.js-transfert-client-type:checked').val() || 'usine';
  }

  function findClient(type, id) {
    var list = clientsOptions[type] || [];
    for (var i = 0; i < list.length; i++) {
      if (String(list[i].id) === String(id)) return list[i];
    }
    return null;
  }

  function fillClientOptions($form, preferredId) {
    var type = getClientType($form);
    var $client = $form.find('.js-transfert-client');
    var list = clientsOptions[type] || [];

    $client.empty().append($('<option></option>').val('').text('-- Choisir un client --'));
    list.forEach(function (item) {
      $client.append(
        $('<option></option>')
          .val(item.id)
          .text(item.label)
          .attr('data-name', item.name)
      );
    });

    $client.val(preferredId ? String(preferredId) : '');

    $form.find('.js-transfert-client-help').text(
      type === 'usine'
        ? 'Liste des usines — tapez pour rechercher.'
        : 'Liste des particuliers — tapez pour rechercher (code / nom).'
    );

    return $client;
  }

  function fillLieuOptions($form, preferredDepart, preferredDestination) {
    var type = getClientType($form);
    var clientId = $form.find('.js-transfert-client').val();
    var client = findClient(type, clientId);
    var sites = (client && client.sites) ? client.sites : [];

    function fillSelect($select, preferred) {
      $select.empty().append(
        $('<option></option>')
          .val('')
          .text(sites.length ? '-- Choisir un site --' : '-- Aucun site pour ce client --')
      );
      sites.forEach(function (site) {
        $select.append($('<option></option>').val(site.value).text(site.label));
      });
      if (preferred) {
        $select.val(preferred);
      }
    }

    var $depart = $form.find('.js-transfert-lieu-depart');
    var $destination = $form.find('.js-transfert-lieu-destination');
    fillSelect($depart, preferredDepart);
    fillSelect($destination, preferredDestination);

    return { depart: $depart, destination: $destination, hasSites: sites.length > 0 };
  }

  function syncClientHidden($form) {
    var $client = $form.find('.js-transfert-client');
    var $opt = $client.find(':selected');
    $form.find('.js-transfert-client-id').val($client.val() || '');
    $form.find('.js-transfert-client-name').val($opt.data('name') || $opt.text() || '');
  }

  function initTransfertForm($modal) {
    var $form = $modal.find('.js-transfert-form');
    if (!$form.length) return;

    var preferredType = $form.data('client-type') || 'usine';
    var preferredClientId = $form.data('client-id') || '';
    var preferredDepart = $form.data('lieu-depart') || '';
    var preferredDestination = $form.data('lieu-destination') || '';

    $form.find('.js-transfert-client-type[value="' + preferredType + '"]').prop('checked', true);

    initSelect2($form.find('.js-transfert-vehicule'), $modal, { tags: true });
    $form.find('.js-transfert-vehicule').off('change.transfertVehicule').on('change.transfertVehicule', function () {
      syncVehiculeId($(this));
    });
    syncVehiculeId($form.find('.js-transfert-vehicule'));

    initSelect2($form.find('.js-transfert-produit'), $modal);

    fillClientOptions($form, preferredClientId);
    initSelect2($form.find('.js-transfert-client'), $modal);
    syncClientHidden($form);

    var lieux = fillLieuOptions($form, preferredDepart, preferredDestination);
    initSelect2(lieux.depart, $modal);
    initSelect2(lieux.destination, $modal);

    $form.find('.js-transfert-client-type').off('change.transfertClientType').on('change.transfertClientType', function () {
      fillClientOptions($form, '');
      initSelect2($form.find('.js-transfert-client'), $modal);
      syncClientHidden($form);
      var next = fillLieuOptions($form, '', '');
      initSelect2(next.depart, $modal);
      initSelect2(next.destination, $modal);
    });

    $form.find('.js-transfert-client').off('change.transfertClient').on('change.transfertClient', function () {
      syncClientHidden($form);
      var next = fillLieuOptions($form, '', '');
      initSelect2(next.depart, $modal);
      initSelect2(next.destination, $modal);
    });
  }

  $(document).ready(function () {
    $(document).on('shown.bs.modal', '#modalCreateTransfert, [id^="modalEditTransfert"]', function () {
      initTransfertForm($(this));
    });

    $(document).on('show.bs.modal', '#modalDechargerTransfert', function (event) {
      var button = event.relatedTarget;
      if (!button) return;

      var $btn = $(button);
      $('#formDechargerTransfert').attr('action', $btn.data('action') || '#');
      $('#dechargeModalDate').text($btn.data('date') || '—');
      $('#dechargeModalVehicule').text($btn.data('vehicule') || '—');
      $('#dechargeModalClient').text($btn.data('client') || '—');
      $('#dechargeModalDepart').text($btn.data('depart') || '—');
      $('#dechargeModalDestination').text($btn.data('destination') || '—');
    });

    $(document).on('show.bs.modal', '#modalPrixUnitaire', function (event) {
      var button = event.relatedTarget;
      if (!button) return;

      var $btn = $(button);
      var prix = $btn.data('prix');
      var poids = $btn.data('poids');

      $('#formPrixUnitaire').attr('action', $btn.data('action') || '#');
      $('#prixModalVehicule').text($btn.data('vehicule') || '—');
      $('#prixModalClient').text($btn.data('client') || '—');
      $('#prixModalPoids').text(poids !== undefined && poids !== null && poids !== ''
        ? Number(poids).toLocaleString('fr-FR')
        : '—');
      $('#prixUnitaireInput').val(prix !== undefined && prix !== null && prix !== '' ? prix : '');
    });

    @if($errors->has('prix_unitaire'))
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPrixUnitaire')).show();
    @endif
  });
})();
</script>
@endsection
