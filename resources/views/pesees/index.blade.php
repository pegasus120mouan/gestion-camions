@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Pesées</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouvellePesee">Nouvelle pesée</button>
    </div>

    <div class="modal fade" id="modalCancelPesee" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="cancelModalTitle">Annuler la pesée</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="#" id="cancelPeseeForm">
              @csrf
              <div class="mb-3">
                <label class="form-label">Motif d'annulation</label>
                <input type="text" name="cancel_reason" class="form-control" required />
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-warning">Annuler</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <form method="GET" action="{{ route('pesees.index') }}" class="mb-4">
      <div class="row g-2">
        <div class="col-md-2">
          <label class="form-label">Du</label>
          <input type="date" name="du" class="form-control" value="{{ request('du') }}" />
        </div>
        <div class="col-md-2">
          <label class="form-label">Au</label>
          <input type="date" name="au" class="form-control" value="{{ request('au') }}" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Pont</label>
          <select name="pont_pesage_id" class="form-select">
            <option value="">-- Tous --</option>
            @foreach($ponts as $p)
              <option value="{{ $p->id }}" {{ (string) request('pont_pesage_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }} - {{ $p->nom }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Camion</label>
          <select name="camion_id" class="form-select">
            <option value="">-- Tous --</option>
            @foreach($camions as $c)
              <option value="{{ $c->id }}" {{ (string) request('camion_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->immatriculation }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Produit</label>
          <select name="produit_id" class="form-select">
            <option value="">-- Tous --</option>
            @foreach($produits as $prod)
              <option value="{{ $prod->id }}" {{ (string) request('produit_id') === (string) $prod->id ? 'selected' : '' }}>{{ $prod->nom }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-12 d-flex justify-content-end">
          <button class="btn btn-outline-secondary" type="submit">Filtrer</button>
        </div>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Statut</th>
              <th>Pont</th>
              <th>Produit</th>
              <th class="text-end">Brut</th>
              <th class="text-end">Après réfraction</th>
              <th class="text-end">Poids vide</th>
              <th class="text-end">Net</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($pesees as $p)
              <tr>
                <td>{{ optional($p->pese_le)->format('d/m/Y H:i') }}</td>
                <td>
                  @if(($p->status ?? 'validated') === 'cancelled')
                    <span class="badge bg-label-danger">Annulée</span>
                  @elseif(($p->status ?? 'validated') === 'draft')
                    <span class="badge bg-label-warning">Brouillon</span>
                  @else
                    <span class="badge bg-label-success">Validée</span>
                  @endif
                </td>
                <td>{{ $p->pontPesage?->code }}</td>
                <td>{{ $p->produit?->nom }}</td>
                <td class="text-end">{{ $p->poids_brut }}</td>
                <td class="text-end">{{ $p->poids_apres_refraction }}</td>
                <td class="text-end">{{ $p->poids_vide }}</td>
                <td class="text-end">{{ $p->poids_net }}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-secondary btn-pesee-details" data-id="{{ $p->id }}" data-bs-toggle="modal" data-bs-target="#modalDetailsPesee" title="Détails">
                    <i class="bx bx-show"></i>
                  </button>
                  <a class="btn btn-sm btn-outline-dark" href="{{ route('pesees.ticket', $p) }}" target="_blank" title="Ticket PDF">
                    <i class="bx bx-receipt"></i>
                  </a>
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('pesees.edit', $p) }}" title="Modifier">
                    <i class="bx bx-edit"></i>
                  </a>
                  @if(($p->status ?? 'validated') !== 'cancelled')
                    <button type="button" class="btn btn-sm btn-outline-warning btn-pesee-cancel" data-id="{{ $p->id }}" data-ref="{{ $p->reference }}" data-bs-toggle="modal" data-bs-target="#modalCancelPesee" title="Annuler">
                      <i class="bx bx-x-circle"></i>
                    </button>
                  @else
                    <form class="d-inline" method="POST" action="{{ route('pesees.validate', $p) }}">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-success" title="Réactiver">
                        <i class="bx bx-check-circle"></i>
                      </button>
                    </form>
                  @endif
                  <form class="d-inline" method="POST" action="{{ route('pesees.destroy', $p) }}" onsubmit="return confirm('Supprimer cette pesée ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="13" class="text-center">Aucune pesée</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $pesees->links() }}
    </div>

    <div class="modal fade" id="modalNouvellePesee" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer une pesée</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('pesees.store') }}">
              @csrf

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Pont</label>
                  <select name="pont_pesage_id" class="form-select" required>
                    <option value="">-- Choisir --</option>
                    @foreach($ponts as $pont)
                      <option value="{{ $pont->id }}" {{ (string) old('pont_pesage_id') === (string) $pont->id ? 'selected' : '' }}>
                        {{ $pont->code }} - {{ $pont->nom }}
                      </option>
                    @endforeach
                  </select>
                  @error('pont_pesage_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Camion</label>
                  <select name="camion_id" class="form-select" required>
                    <option value="">-- Choisir --</option>
                    @foreach($camions as $camion)
                      <option value="{{ $camion->id }}" {{ (string) old('camion_id') === (string) $camion->id ? 'selected' : '' }}>
                        {{ $camion->immatriculation }}
                      </option>
                    @endforeach
                  </select>
                  @error('camion_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Produit</label>
                  <select name="produit_id" class="form-select" required>
                    <option value="">-- Choisir --</option>
                    @foreach($produits as $prod)
                      <option value="{{ $prod->id }}" {{ (string) old('produit_id') === (string) $prod->id ? 'selected' : '' }}>
                        {{ $prod->nom }}
                      </option>
                    @endforeach
                  </select>
                  @error('produit_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Agent</label>
                  <select name="agent_id" class="form-select" required>
                    <option value="">-- Choisir --</option>
                    @foreach($agents as $a)
                      <option value="{{ $a->id }}" {{ (string) old('agent_id') === (string) $a->id ? 'selected' : '' }}>
                        {{ $a->name }} {{ $a->prenom }} ({{ $a->login }})
                      </option>
                    @endforeach
                  </select>
                  @error('agent_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Chauffeur (optionnel)</label>
                  <select name="chauffeur_id" class="form-select">
                    <option value="">-- Aucun --</option>
                    @foreach($chauffeurs as $ch)
                      <option value="{{ $ch->id }}" {{ (string) old('chauffeur_id') === (string) $ch->id ? 'selected' : '' }}>
                        {{ $ch->name }} {{ $ch->prenom }} ({{ $ch->login }})
                      </option>
                    @endforeach
                  </select>
                  @error('chauffeur_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Date/heure (optionnel)</label>
                  <input type="datetime-local" name="pese_le" class="form-control" value="{{ old('pese_le') ? (str_contains(old('pese_le'), 'T') ? old('pese_le') : \Carbon\Carbon::createFromFormat('d/m/Y', old('pese_le'))->format('Y-m-d\\TH:i')) : \Carbon\Carbon::now()->format('Y-m-d\\TH:i') }}" />
                  @error('pese_le')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Poids brut</label>
                  <input type="number" step="0.001" name="poids_brut" class="form-control" value="{{ old('poids_brut') }}" required />
                  @error('poids_brut')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Poids vide (optionnel)</label>
                  <input type="number" step="0.001" name="poids_vide" class="form-control" value="{{ old('poids_vide', 0) }}" />
                  @error('poids_vide')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    @if ($errors->any() || request()->boolean('create'))
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var el = document.getElementById('modalNouvellePesee');
          if (el && window.bootstrap) {
            new bootstrap.Modal(el).show();
          }
        });
      </script>
    @endif

    <div class="modal fade" id="modalDetailsPesee" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Détails de la pesée</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4 mb-3">
                <div class="text-muted">Date/heure</div>
                <div class="fw-semibold" id="pesee_detail_pese_le">-</div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="text-muted">Référence</div>
                <div class="fw-semibold" id="pesee_detail_reference">-</div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="text-muted">Pont</div>
                <div class="fw-semibold" id="pesee_detail_pont">-</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <div class="text-muted">Camion</div>
                <div class="fw-semibold" id="pesee_detail_camion">-</div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="text-muted">Produit</div>
                <div class="fw-semibold" id="pesee_detail_produit">-</div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="text-muted">Agent</div>
                <div class="fw-semibold" id="pesee_detail_agent">-</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <div class="text-muted">Chauffeur</div>
                <div class="fw-semibold" id="pesee_detail_chauffeur">-</div>
              </div>
              <div class="col-md-8 mb-3">
                <div class="text-muted">Notes</div>
                <div class="fw-semibold" id="pesee_detail_notes">-</div>
              </div>
            </div>

            <hr />

            <div class="row">
              <div class="col-md-3 mb-3">
                <div class="text-muted">Poids brut</div>
                <div class="fw-semibold" id="pesee_detail_poids_brut">-</div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="text-muted">Tare</div>
                <div class="fw-semibold" id="pesee_detail_tare">-</div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="text-muted">Après réfraction</div>
                <div class="fw-semibold" id="pesee_detail_apres_refraction">-</div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="text-muted">Poids vide</div>
                <div class="fw-semibold" id="pesee_detail_poids_vide">-</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3 mb-3">
                <div class="text-muted">Poids net</div>
                <div class="fw-semibold" id="pesee_detail_poids_net">-</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <a href="#" id="pesee_detail_edit_link" class="btn btn-outline-primary">Modifier</a>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        function formatPeseLe(value) {
          if (!value) {
            return '-';
          }

          var dt = new Date(value);
          if (isNaN(dt.getTime())) {
            return String(value).replace(/\.\d+Z$/, '');
          }

          var pad = function (n) { return String(n).padStart(2, '0'); };

          return pad(dt.getDate()) + '/' + pad(dt.getMonth() + 1) + '/' + dt.getFullYear() + ' ' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
        }

        document.querySelectorAll('.btn-pesee-details').forEach(function (btn) {
          btn.addEventListener('click', async function () {
            var id = btn.getAttribute('data-id');
            var url = "{{ url('/pesees') }}/" + id;

            document.getElementById('pesee_detail_pese_le').textContent = 'Chargement...';

            try {
              var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
              if (!res.ok) {
                throw new Error('HTTP ' + res.status);
              }

              var json = await res.json();
              var d = json.data || {};

              document.getElementById('pesee_detail_pese_le').textContent = formatPeseLe(d.pese_le);
              document.getElementById('pesee_detail_reference').textContent = d.reference || '-';
              document.getElementById('pesee_detail_pont').textContent = (d.pont_pesage ? (d.pont_pesage.code + ' - ' + d.pont_pesage.nom) : '-');
              document.getElementById('pesee_detail_camion').textContent = (d.camion ? d.camion.immatriculation : '-');
              document.getElementById('pesee_detail_produit').textContent = (d.produit ? d.produit.nom : '-');
              document.getElementById('pesee_detail_agent').textContent = (d.agent ? ((d.agent.name || '') + ' ' + (d.agent.prenom || '')).trim() : '-');
              document.getElementById('pesee_detail_chauffeur').textContent = (d.chauffeur ? ((d.chauffeur.name || '') + ' ' + (d.chauffeur.prenom || '')).trim() : '-');
              document.getElementById('pesee_detail_notes').textContent = d.notes || '-';

              document.getElementById('pesee_detail_poids_brut').textContent = d.poids_brut ?? '-';
              document.getElementById('pesee_detail_tare').textContent = d.tare ?? '-';
              document.getElementById('pesee_detail_apres_refraction').textContent = d.poids_apres_refraction ?? '-';
              document.getElementById('pesee_detail_poids_vide').textContent = d.poids_vide ?? '-';
              document.getElementById('pesee_detail_poids_net').textContent = d.poids_net ?? '-';

              document.getElementById('pesee_detail_edit_link').setAttribute('href', "{{ url('/pesees') }}/" + id + "/edit");
            } catch (e) {
              document.getElementById('pesee_detail_pese_le').textContent = 'Erreur de chargement';
              document.getElementById('pesee_detail_reference').textContent = '-';
              document.getElementById('pesee_detail_pont').textContent = '-';
              document.getElementById('pesee_detail_camion').textContent = '-';
              document.getElementById('pesee_detail_produit').textContent = '-';
              document.getElementById('pesee_detail_agent').textContent = '-';
              document.getElementById('pesee_detail_chauffeur').textContent = '-';
              document.getElementById('pesee_detail_notes').textContent = '-';
              document.getElementById('pesee_detail_poids_brut').textContent = '-';
              document.getElementById('pesee_detail_tare').textContent = '-';
              document.getElementById('pesee_detail_apres_refraction').textContent = '-';
              document.getElementById('pesee_detail_poids_vide').textContent = '-';
              document.getElementById('pesee_detail_poids_net').textContent = '-';
              document.getElementById('pesee_detail_edit_link').setAttribute('href', '#');
            }
          });
        });

        var cancelForm = document.getElementById('cancelPeseeForm');
        var cancelTitle = document.getElementById('cancelModalTitle');
        document.querySelectorAll('.btn-pesee-cancel').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            var ref = btn.getAttribute('data-ref') || '';
            if (cancelTitle) {
              cancelTitle.textContent = ref ? ("Annuler la pesée " + ref) : 'Annuler la pesée';
            }
            if (cancelForm) {
              cancelForm.setAttribute('action', "{{ url('/pesees') }}/" + id + "/cancel");
              var input = cancelForm.querySelector('input[name="cancel_reason"]');
              if (input) {
                input.value = '';
                input.focus();
              }
            }
          });
        });
      });
    </script>
  </div>
</div>
@endsection
