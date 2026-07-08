@extends('layout.main')
@section('content')
@include('gestion_financiere._table_financiere_styles')
<div class="content-wrapper gf-financier-page">
  <div class="container-xxl flex-grow-1 container-p-y">
    @php
      $nomComplet = $agent['nom_complet'] ?? trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? ''));
      $idAgent = (int) ($agent['id_agent'] ?? 0);
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière — {{ $nomComplet ?: 'Agent' }}</h4>
        @if(!empty($agent['numero_agent']))
          <span class="badge bg-label-primary me-1">{{ $agent['numero_agent'] }}</span>
        @endif
        @if(!empty($agent['contact']))
          <span class="badge bg-secondary">{{ $agent['contact'] }}</span>
        @endif
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('gestionfinanciere.synthese_produit', $queryFiltres ?? []) }}" class="btn btn-outline-info btn-sm">
          <i class="bx bx-pie-chart-alt me-1"></i>Synthèse produits
        </a>
        <a href="{{ route('gestionfinanciere.montant_agent', $queryFiltres ?? []) }}" class="btn btn-secondary">
          <i class="bx bx-arrow-back me-1"></i>Retour
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        @if(session('recu_paiement_id'))
          <a href="{{ route('gestionfinanciere.recus.pdf', session('recu_paiement_id')) }}" target="_blank" class="alert-link ms-2">
            <i class="bx bx-file me-1"></i>Voir le reçu
          </a>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @include('gestion_financiere._filtres_montant_agent', [
      'actionRoute' => route('gestionfinanciere.agent.show', ['id_agent' => $idAgent]),
      'filtres' => $filtres,
      'filtresActifs' => $filtresActifs,
      'produits' => $produits,
      'usines' => $usines,
      'showAvanceButton' => true,
    ])

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">
              Montant dû
              @if(!empty($filtresActifs))
                <small class="fw-normal">(filtre)</small>
              @endif
            </h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
            @if(!empty($filtresActifs))
              <small class="text-muted">Total agent : {{ number_format($montantDuGlobal, 0, ',', ' ') }} FCFA</small>
            @else
              <small class="text-muted">Tickets validés, y compris ceux déjà sur bordereau</small>
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
          <div class="card-body">
            <h6 class="card-title" style="color: #0f5132;">Montant payé</h6>
            <h3 class="mb-0" style="color: #0f5132;">{{ number_format($montantPayeBordereaux, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">
              Paiements enregistrés sur les bordereaux
              @if(($montantAvances ?? 0) > 0)
                · Avances (financement) : {{ number_format($montantAvances, 0, ',', ' ') }} FCFA
              @endif
            </small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
          <div class="card-body">
            <h6 class="card-title" style="color: #664d03;">Reste à payer</h6>
            <h3 class="mb-0" style="color: #664d03;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Montant dû − montant payé (bordereaux)</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
            <h5 class="card-title mb-0" style="color: #842029;">
              <i class="bx bx-file me-2"></i>Gestion bordereaux
            </h5>
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalGenererBordereau">
              <i class="bx bx-plus me-1"></i>Générer un bordereau
            </button>
          </div>
          <div class="table-responsive gf-table-wrap">
            <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
              <thead>
                <tr>
                  <th>N° bordereau</th>
                  <th>Généré le</th>
                  <th>Période</th>
                  <th class="text-end">Fiches</th>
                  <th class="text-end">Poids</th>
                  <th class="text-end">Montant</th>
                  <th class="text-end">Montant payé</th>
                  <th class="text-end">Reste à payer</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($bordereaux ?? [] as $bordereau)
                  @php
                    $resteBordereau = (int) round($bordereau->reste_a_payer);
                    $montantPayeBordereau = (int) round((float) ($bordereau->montant_paye ?? 0));
                  @endphp
                  <tr>
                    <td>
                      <a href="{{ route('gestionfinanciere.agent.bordereau.pdf', ['id_agent' => $idAgent, 'id' => $bordereau->id]) }}" target="_blank" rel="noopener" class="fw-bold text-primary text-decoration-none" title="Ouvrir la liste des fiches (PDF)">
                        {{ $bordereau->numero }}
                      </a>
                    </td>
                    <td>{{ $bordereau->date_generation ? $bordereau->date_generation->format('d/m/Y') : '-' }}</td>
                    <td>
                      {{ $bordereau->date_debut ? $bordereau->date_debut->format('d/m/Y') : '-' }}
                      →
                      {{ $bordereau->date_fin ? $bordereau->date_fin->format('d/m/Y') : '-' }}
                    </td>
                    <td class="text-end">{{ count($bordereau->fiches_data ?? []) }}</td>
                    <td class="text-end">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }} kg</td>
                    <td class="text-end text-danger fw-bold">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</td>
                    <td class="text-end text-success">{{ number_format($montantPayeBordereau, 0, ',', ' ') }} FCFA</td>
                    <td class="text-end">
                      @if($resteBordereau > 0)
                        <span class="text-danger fw-bold">{{ number_format($resteBordereau, 0, ',', ' ') }} FCFA</span>
                      @else
                        <span class="text-muted">0 FCFA</span>
                      @endif
                    </td>
                    <td class="text-center">
                      @if($resteBordereau > 0)
                        <button type="button"
                          class="btn btn-sm btn-outline-success btn-paiement-bordereau"
                          title="Enregistrer un paiement"
                          data-bs-toggle="modal"
                          data-bs-target="#modalPaiementBordereau"
                          data-bordereau-id="{{ $bordereau->id }}"
                          data-bordereau-numero="{{ $bordereau->numero }}"
                          data-bordereau-reste="{{ $resteBordereau }}">
                          <i class="bx bx-money"></i>
                        </button>
                      @endif
                      <a href="{{ route('gestionfinanciere.agent.bordereau.pdf', ['id_agent' => $idAgent, 'id' => $bordereau->id]) }}" target="_blank" class="btn btn-sm btn-outline-info" title="PDF">
                        <i class="bx bx-printer"></i>
                      </a>
                      <form method="POST" action="{{ route('gestionfinanciere.agent.bordereau.destroy', ['id_agent' => $idAgent, 'id' => $bordereau->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce bordereau ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center text-muted py-4">Aucun bordereau généré pour cet agent</td>
                  </tr>
                @endforelse
              </tbody>
              @if(($bordereaux ?? collect())->count() > 0)
                <tfoot>
                  <tr>
                    <td colspan="5" class="text-end"><strong>Totaux</strong></td>
                    <td class="text-end text-danger fw-bold">{{ number_format($bordereaux->sum('montant_total'), 0, ',', ' ') }} FCFA</td>
                    <td class="text-end text-success fw-bold">{{ number_format($bordereaux->sum('montant_paye'), 0, ',', ' ') }} FCFA</td>
                    <td class="text-end text-danger fw-bold">{{ number_format($bordereaux->sum(fn ($b) => $b->reste_a_payer), 0, ',', ' ') }} FCFA</td>
                    <td></td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
            <h5 class="card-title mb-0" style="color: #842029;">
              <i class="bx bx-file me-2"></i>Détail des tickets ({{ count($fichesAvecMontant) }})
            </h5>
          </div>
          <div class="table-responsive gf-table-wrap">
            <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Véhicule</th>
                  <th>Produit</th>
                  <th>Usine</th>
                  <th>N° ticket</th>
                  <th>Fiche</th>
                  <th class="text-end">Poids</th>
                  <th class="text-end">PU</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($groupesProduitUsine as $groupe)
                  @foreach($groupe['usines'] as $blocUsine)
                    @foreach($blocUsine['lignes'] as $item)
                      @php
                        $dateLigne = $item['ticket']->date_ticket ?? $item['fiche']->date_chargement;
                      @endphp
                      <tr>
                        <td>{{ $dateLigne ? $dateLigne->format('d/m/Y') : '-' }}</td>
                        <td>{{ $item['fiche']->matricule_vehicule ?? '-' }}</td>
                        <td>
                          @php
                            $ticketLigne = $item['ticket'];
                            $surBordereau = (bool) ($ticketLigne->bordereau_agent_id ?? false)
                              || (!empty($item['a_fiche']) && (bool) ($item['fiche']->bordereau_agent_id ?? false));
                          @endphp
                          @if($item['fiche']->nom_produit)
                            <span class="badge bg-label-info">{{ $item['fiche']->nom_produit }}</span>
                          @elseif($surBordereau)
                            <span class="text-muted">—</span>
                          @else
                            <form
                              method="POST"
                              action="{{ route('gestionfinanciere.agent.ticket.produit', ['id_agent' => $idAgent, 'id_ticket' => $ticketLigne->id_ticket]) }}"
                              class="gf-produit-select-form"
                            >
                              @csrf
                              <select name="produit_id" class="form-select form-select-sm" required onchange="this.form.submit()">
                                <option value="" selected disabled>Choisir un produit</option>
                                @foreach($produits as $produit)
                                  <option value="{{ $produit->id }}">{{ $produit->nom }}</option>
                                @endforeach
                              </select>
                            </form>
                          @endif
                        </td>
                        <td><small>{{ $item['fiche']->usine ?? '—' }}</small></td>
                        <td>
                          @if($item['ticket']->numero_ticket ?? $item['fiche']->numero_ticket)
                            <code class="small">{{ $item['ticket']->numero_ticket ?? $item['fiche']->numero_ticket }}</code>
                          @else
                            <span class="text-muted">—</span>
                          @endif
                        </td>
                        <td>
                          @if(!empty($item['a_fiche']))
                            <span class="badge bg-label-success">{{ $item['fiche']->numero_fiche }}</span>
                          @else
                            <span class="badge bg-label-secondary">Sans fiche</span>
                          @endif
                        </td>
                        <td class="text-end">
                          @if(($item['poids_effectif'] ?? 0) > 0)
                            {{ number_format((float) $item['poids_effectif'], 0, ',', ' ') }}
                          @else
                            —
                          @endif
                        </td>
                        <td class="text-end">
                          @if($item['prix_unitaire'] !== null)
                            {{ number_format($item['prix_unitaire'], 0, ',', ' ') }}
                          @else
                            <span class="text-muted">—</span>
                          @endif
                        </td>
                        <td class="text-end text-danger">{{ $item['montant'] > 0 ? number_format($item['montant'], 0, ',', ' ') . ' FCFA' : '—' }}</td>
                      </tr>
                    @endforeach
                    <tr class="table-secondary">
                      <td colspan="6" class="text-end"><strong>Sous-total {{ $blocUsine['usine'] }}</strong></td>
                      <td class="text-end"><strong>{{ number_format($blocUsine['poids_total'], 0, ',', ' ') }}</strong></td>
                      <td></td>
                      <td class="text-end text-danger"><strong>{{ number_format($blocUsine['montant_total'], 0, ',', ' ') }} FCFA</strong></td>
                    </tr>
                  @endforeach
                  <tr class="table-warning">
                    <td colspan="6" class="text-end"><strong>Total {{ $groupe['produit'] }}</strong></td>
                    <td class="text-end"><strong>{{ number_format($groupe['poids_total'], 0, ',', ' ') }}</strong></td>
                    <td></td>
                    <td class="text-end text-danger"><strong>{{ number_format($groupe['montant_total'], 0, ',', ' ') }} FCFA</strong></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center">Aucun ticket validé</td>
                  </tr>
                @endforelse
              </tbody>
              @if(count($fichesAvecMontant) > 0)
                <tfoot>
                  <tr class="table-danger">
                    <td colspan="8" class="text-end"><strong>Total affiché</strong></td>
                    <td class="text-end"><strong>{{ number_format($montantDu, 0, ',', ' ') }} FCFA</strong></td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #d1e7dd; border-bottom: 1px solid #badbcc;">
            <div>
              <h5 class="card-title mb-0" style="color: #0f5132;"><i class="bx bx-plus-circle me-2"></i>Paiements et avances ({{ $paiements->count() }})</h5>
              <small class="text-muted">Paiements sur bordereaux ou avances directes</small>
            </div>
          </div>
          <div class="table-responsive gf-table-wrap">
            <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Bordereau</th>
                  <th>Mode</th>
                  <th class="text-end">Montant</th>
                  <th class="text-center">Reçu</th>
                </tr>
              </thead>
              <tbody>
                @forelse($paiements as $paiement)
                  <tr>
                    <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}</td>
                    <td>
                      @if($paiement->bordereau)
                        <span class="badge bg-label-primary">{{ $paiement->bordereau->numero }}</span>
                      @else
                        <span class="badge bg-label-success">Avance</span>
                      @endif
                    </td>
                    <td>
                      @if($paiement->mode_paiement)
                        <span class="badge bg-info">{{ $paiement->mode_paiement }}</span>
                      @else
                        -
                      @endif
                    </td>
                    <td class="text-end text-success">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                    <td class="text-center">
                      <a href="{{ route('gestionfinanciere.recus.pdf', $paiement->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Reçu PDF">
                        <i class="bx bx-file"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Aucun paiement enregistré</td>
                  </tr>
                @endforelse
              </tbody>
              @if($paiements->count() > 0)
                <tfoot>
                  <tr class="table-success">
                    <td colspan="3"><strong>Total</strong></td>
                    <td class="text-end"><strong>{{ number_format($montantPayeTotal ?? $montantPaye, 0, ',', ' ') }} FCFA</strong></td>
                    <td></td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAvanceAgent" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-wallet me-2"></i>Avance agent</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('gestionfinanciere.agent.avance.store', ['id_agent' => $idAgent]) }}" id="formAvanceAgent">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Reste à payer :</strong> {{ number_format($resteAPayer, 0, ',', ' ') }} FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant avance (FCFA) <span class="text-danger">*</span></label>
            <input type="text" name="montant" id="avanceAgentMontant" class="form-control" required placeholder="Ex: 1 000 000" inputmode="numeric" autocomplete="off" />
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select">
              <option value="Espèces" selected>Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" placeholder="Optionnel" />
          </div>
          <div class="mb-3">
            <label class="form-label">Commentaire</label>
            <input type="text" name="commentaire" class="form-control" value="Avance" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success" id="btnSubmitAvance" disabled>
            <i class="bx bx-save me-1"></i>Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPaiementBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formPaiementBordereau" method="POST" action="">
        @csrf
        <div class="modal-body">
          <div class="alert alert-secondary mb-2">
            <strong>Bordereau :</strong> <span id="paiementBordereauNumero">—</span>
          </div>
          <div class="alert alert-info">
            <strong>Reste à payer :</strong> <span id="paiementBordereauReste">0</span> FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="paiementBordereauMontant" class="form-control montant-input-bordereau" required placeholder="Ex: 4 685 000" inputmode="numeric" autocomplete="off" />
            <small class="text-muted">Vous pouvez saisir un montant supérieur au reste du bordereau.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select">
              <option value="">-- Sélectionner --</option>
              <option value="Espèces">Espèces</option>
              <option value="Virement">Virement</option>
              <option value="Chèque">Chèque</option>
              <option value="Mobile Money">Mobile Money</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" placeholder="Optionnel" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

@include('gestion_financiere._filtres_montant_agent_js')

<div class="modal fade" id="modalGenererBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-file me-2"></i>Générer un bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('gestionfinanciere.agent.bordereau.store', ['id_agent' => $idAgent]) }}" id="formGenererBordereau">
        @csrf
        <div class="modal-body">
          <p class="text-muted small mb-3">
            Un numéro de bordereau sera attribué automatiquement à la génération
            @if(!empty($exempleNumeroBordereau))
              (ex.&nbsp;: <strong>{{ $exempleNumeroBordereau }}</strong>).
            @else
              (ex.&nbsp;: <strong>BORD-XX1</strong>).
            @endif
          </p>
          <div class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
              <label class="form-label">Période début <span class="text-danger">*</span></label>
              <input type="date" name="date_debut" id="bordereau_date_debut" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Période fin <span class="text-danger">*</span></label>
              <input type="date" name="date_fin" id="bordereau_date_fin" class="form-control" required>
            </div>
            <div class="col-md-4">
              <button type="button" class="btn btn-outline-primary w-100" id="btnChargerFichesBordereau">
                <i class="bx bx-search me-1"></i>Charger les fiches déchargées
              </button>
            </div>
          </div>

          <div id="bordereauChargement" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Chargement des fiches…</p>
          </div>

          <div id="bordereauAucuneFiche" class="alert alert-warning d-none mb-0">
            Aucun ticket validé disponible sur cette période (tickets déjà inclus dans un bordereau exclus).
          </div>

          <div id="bordereauListeFiches" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToutSelectionnerBordereau">Tout sélectionner</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToutDeselectionnerBordereau">Tout désélectionner</button>
              </div>
              <div class="text-end">
                <span class="badge bg-label-info me-1" id="bordereauNbSelection">0 fiche(s)</span>
                <span class="badge bg-label-danger" id="bordereauMontantSelection">0 FCFA</span>
              </div>
            </div>
            <div class="table-responsive gf-table-wrap" style="max-height: 360px; overflow-y: auto;">
              <table class="table table-sm table-bordered table-hover align-middle gf-table-financier mb-0">
                <thead class="sticky-top">
                  <tr>
                    <th style="width:40px"><input type="checkbox" id="checkAllFichesBordereau" checked></th>
                    <th>N° fiche</th>
                    <th>N° ticket</th>
                    <th>Déchargement</th>
                    <th>Véhicule</th>
                    <th>Produit</th>
                    <th>Usine</th>
                    <th class="text-end">Poids</th>
                    <th class="text-end">Montant</th>
                  </tr>
                </thead>
                <tbody id="tbodyFichesBordereau"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger" id="btnSubmitBordereau" disabled>
            <i class="bx bx-check me-1"></i>Générer le bordereau
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
  var urlFiches = @json(route('gestionfinanciere.agent.bordereau.fiches', ['id_agent' => $idAgent]));
  var tbody = document.getElementById('tbodyFichesBordereau');
  var listeBlock = document.getElementById('bordereauListeFiches');
  var aucuneBlock = document.getElementById('bordereauAucuneFiche');
  var chargementBlock = document.getElementById('bordereauChargement');
  var btnSubmit = document.getElementById('btnSubmitBordereau');
  var nbSel = document.getElementById('bordereauNbSelection');
  var montantSel = document.getElementById('bordereauMontantSelection');
  var checkAll = document.getElementById('checkAllFichesBordereau');

  function formatNombre(n) {
    return new Intl.NumberFormat('fr-FR').format(n);
  }

  function formatMontantSaisie(value) {
    var digits = String(value || '').replace(/\D/g, '');
    if (!digits) {
      return '';
    }
    return parseInt(digits, 10).toLocaleString('fr-FR').replace(/\u202F/g, ' ').replace(/,/g, ' ');
  }

  function majTotauxSelection() {
    var checks = tbody.querySelectorAll('.fiche-bordereau-check:checked');
    var montant = 0;
    checks.forEach(function(c) {
      montant += parseInt(c.dataset.montant || '0', 10);
    });
    nbSel.textContent = checks.length + ' ticket(s)';
    montantSel.textContent = formatNombre(montant) + ' FCFA';
    btnSubmit.disabled = checks.length === 0;
    if (checkAll) {
      var all = tbody.querySelectorAll('.fiche-bordereau-check');
      checkAll.checked = all.length > 0 && checks.length === all.length;
    }
  }

  function renderFiches(fiches) {
    tbody.innerHTML = '';
    fiches.forEach(function(f) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="checkbox" class="form-check-input fiche-bordereau-check" name="ticket_ids[]" value="' + f.ticket_id + '" data-montant="' + f.montant + '" checked></td>' +
        '<td><small>' + (f.numero_ticket || '—') + '</small></td>' +
        '<td><small>' + (f.a_fiche ? (f.numero_fiche || ('#' + (f.fiche_id || ''))) : 'Sans fiche') + '</small></td>' +
        '<td><small>' + (f.date_dechargement ? f.date_dechargement.split('-').reverse().join('/') : '-') + '</small></td>' +
        '<td>' + (f.matricule_vehicule || '-') + '</td>' +
        '<td><small>' + (f.nom_produit || '—') + '</small></td>' +
        '<td><small>' + (f.usine || '—') + '</small></td>' +
        '<td class="text-end">' + formatNombre(Math.round(f.poids || 0)) + '</td>' +
        '<td class="text-end text-danger">' + formatNombre(f.montant || 0) + '</td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) {
      c.addEventListener('change', majTotauxSelection);
    });

    listeBlock.classList.remove('d-none');
    majTotauxSelection();
  }

  document.getElementById('btnChargerFichesBordereau').addEventListener('click', function() {
    var debut = document.getElementById('bordereau_date_debut').value;
    var fin = document.getElementById('bordereau_date_fin').value;
    if (!debut || !fin) {
      alert('Indiquez la période début et fin.');
      return;
    }

    listeBlock.classList.add('d-none');
    aucuneBlock.classList.add('d-none');
    chargementBlock.classList.remove('d-none');
    btnSubmit.disabled = true;

    var params = new URLSearchParams({ date_debut: debut, date_fin: fin });
    fetch(urlFiches + '?' + params.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        chargementBlock.classList.add('d-none');
        if (!data.fiches || data.fiches.length === 0) {
          aucuneBlock.classList.remove('d-none');
          return;
        }
        renderFiches(data.fiches);
      })
      .catch(function() {
        chargementBlock.classList.add('d-none');
        alert('Impossible de charger les fiches.');
      });
  });

  if (checkAll) {
    checkAll.addEventListener('change', function() {
      tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) {
        c.checked = checkAll.checked;
      });
      majTotauxSelection();
    });
  }

  document.getElementById('btnToutSelectionnerBordereau').addEventListener('click', function() {
    tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) { c.checked = true; });
    majTotauxSelection();
  });

  document.getElementById('btnToutDeselectionnerBordereau').addEventListener('click', function() {
    tbody.querySelectorAll('.fiche-bordereau-check').forEach(function(c) { c.checked = false; });
    majTotauxSelection();
  });

  document.getElementById('formGenererBordereau').addEventListener('submit', function(e) {
    if (tbody.querySelectorAll('.fiche-bordereau-check:checked').length === 0) {
      e.preventDefault();
      alert('Sélectionnez au moins une fiche.');
    }
  });

  document.getElementById('modalGenererBordereau').addEventListener('hidden.bs.modal', function() {
    tbody.innerHTML = '';
    listeBlock.classList.add('d-none');
    aucuneBlock.classList.add('d-none');
    chargementBlock.classList.add('d-none');
    btnSubmit.disabled = true;
  });

  var urlPaiementBordereauBase = @json(url('/gestion-financiere/agent-financier/' . $idAgent . '/bordereaux'));
  var formPaiementBordereau = document.getElementById('formPaiementBordereau');
  var inputMontantBordereau = document.getElementById('paiementBordereauMontant');

  if (inputMontantBordereau) {
    inputMontantBordereau.addEventListener('input', function() {
      var cursorPos = this.selectionStart;
      var oldLength = this.value.length;
      this.value = formatMontantSaisie(this.value);
      var newLength = this.value.length;
      cursorPos = cursorPos + (newLength - oldLength);
      this.setSelectionRange(cursorPos, cursorPos);
    });
  }

  if (formPaiementBordereau) {
    formPaiementBordereau.addEventListener('submit', function() {
      if (inputMontantBordereau) {
        inputMontantBordereau.value = inputMontantBordereau.value.replace(/\s/g, '');
      }
    });
  }

  document.querySelectorAll('.btn-paiement-bordereau').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.getAttribute('data-bordereau-id');
      var numero = btn.getAttribute('data-bordereau-numero');
      var reste = parseInt(btn.getAttribute('data-bordereau-reste') || '0', 10);

      formPaiementBordereau.action = urlPaiementBordereauBase + '/' + id + '/paiement';
      document.getElementById('paiementBordereauNumero').textContent = numero || '—';
      document.getElementById('paiementBordereauReste').textContent = formatNombre(reste);
      inputMontantBordereau.value = reste > 0 ? formatMontantSaisie(String(reste)) : '';
    });
  });

  var inputAvanceMontant = document.getElementById('avanceAgentMontant');
  var btnSubmitAvance = document.getElementById('btnSubmitAvance');
  var formAvanceAgent = document.getElementById('formAvanceAgent');

  function syncAvanceSubmitButton() {
    if (!btnSubmitAvance || !inputAvanceMontant) return;
    var digits = String(inputAvanceMontant.value || '').replace(/\D/g, '');
    btnSubmitAvance.disabled = !digits || parseInt(digits, 10) < 1;
  }

  if (inputAvanceMontant) {
    inputAvanceMontant.addEventListener('input', function() {
      var cursorPos = this.selectionStart;
      var oldLength = this.value.length;
      this.value = formatMontantSaisie(this.value);
      var newLength = this.value.length;
      cursorPos = cursorPos + (newLength - oldLength);
      this.setSelectionRange(cursorPos, cursorPos);
      syncAvanceSubmitButton();
    });
  }

  if (formAvanceAgent) {
    formAvanceAgent.addEventListener('submit', function() {
      if (inputAvanceMontant) {
        inputAvanceMontant.value = inputAvanceMontant.value.replace(/\s/g, '');
      }
      if (btnSubmitAvance) {
        btnSubmitAvance.disabled = true;
        btnSubmitAvance.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';
      }
    });
  }

  var modalAvanceAgent = document.getElementById('modalAvanceAgent');
  if (modalAvanceAgent) {
    modalAvanceAgent.addEventListener('shown.bs.modal', syncAvanceSubmitButton);
    modalAvanceAgent.addEventListener('hidden.bs.modal', function() {
      if (inputAvanceMontant) inputAvanceMontant.value = '';
      if (btnSubmitAvance) {
        btnSubmitAvance.disabled = true;
        btnSubmitAvance.innerHTML = '<i class="bx bx-save me-1"></i>Enregistrer';
      }
    });
  }
})();
</script>
@if(session('recu_paiement_id'))
<script>
  window.open(@json(route('gestionfinanciere.recus.pdf', session('recu_paiement_id'))), '_blank');
</script>
@endif
@endsection
