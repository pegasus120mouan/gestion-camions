@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Situation financière - {{ $transporteur->nom }} {{ $transporteur->prenoms }}</h4>
        <span class="badge bg-label-primary">{{ $transporteur->code }}</span>
        <span class="badge bg-secondary ms-1">{{ $transporteur->vehicules->count() }} camion(s)</span>
      </div>
      <div>
        <a href="{{ route('avances_transporteur.show', $transporteur) }}" class="btn btn-success me-2">
          <i class="bx bx-wallet me-1"></i>Avance
        </a>
        <a href="{{ route('gestionfinanciere.montant_transporteur') }}" class="btn btn-secondary">
          <i class="bx bx-arrow-back me-1"></i>Retour
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row mb-3">
      <div class="col-12">
        <div class="d-flex justify-content-center">
          <a href="{{ route('avances_transporteur.show', $transporteur) }}"
             class="badge rounded-pill text-decoration-none px-4 py-3 d-inline-flex align-items-center gap-2"
             style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); font-size: 1rem; font-weight: 600;">
            <i class="bx bx-wallet text-white"></i>
            <span class="text-white">
              Montant avance : {{ number_format($montantAvancesTransporteur ?? 0, 0, ',', ' ') }} FCFA
            </span>
          </a>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
          <div class="card-body">
            <h6 class="card-title" style="color: #842029;">Montant dû</h6>
            <h3 class="mb-0" style="color: #842029;">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">Tickets / fiches liés au transporteur</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card" style="background-color: #d1e7dd; border-left: 4px solid #198754;">
          <div class="card-body">
            <h6 class="card-title" style="color: #0f5132;">Montant payé</h6>
            <h3 class="mb-0" style="color: #0f5132;">{{ number_format($montantPayeSansAvances ?? $montantPaye, 0, ',', ' ') }} FCFA</h3>
            <small class="text-muted">
              Paiements fiches / bordereaux
              @if(($montantAvancesTransporteur ?? 0) > 0)
                · Avances : {{ number_format($montantAvancesTransporteur, 0, ',', ' ') }} FCFA
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
            <small class="text-muted">Montant dû − montant payé − avances</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Filtres de recherche</h5>
          </div>
          <div class="card-body">
            <form method="GET" action="{{ route('gestionfinanciere.transporteur.show', $transporteur) }}">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Véhicule</label>
                  <select name="vehicule" class="form-select">
                    <option value="">Tous les véhicules</option>
                    @foreach($vehicules as $vehicule)
                      <option value="{{ $vehicule }}" {{ request('vehicule') == $vehicule ? 'selected' : '' }}>{{ $vehicule }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Date début</label>
                  <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Date fin</label>
                  <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary me-2"><i class="bx bx-search"></i></button>
                  <a href="{{ route('gestionfinanciere.transporteur.show', $transporteur) }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8d7da; border-bottom: 1px solid #f5c2c7;">
            <h5 class="card-title mb-0" style="color: #842029;">
              <i class="bx bx-file me-2"></i>Gestion bordereaux
            </h5>
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalGenererBordereau">
              <i class="bx bx-plus me-1"></i>Générer un bordereau
            </button>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-sm table-bordered table-hover align-middle mb-0">
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
                      <a href="{{ route('gestionfinanciere.transporteur.bordereau.show', ['transporteur' => $transporteur->id, 'id' => $bordereau->id]) }}" class="fw-bold text-primary text-decoration-none">
                        {{ $bordereau->numero }}
                      </a>
                    </td>
                    <td>{{ $bordereau->date_generation?->format('d/m/Y') ?? '-' }}</td>
                    <td>
                      {{ $bordereau->date_debut?->format('d/m/Y') ?? '-' }}
                      →
                      {{ $bordereau->date_fin?->format('d/m/Y') ?? '-' }}
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
                    <td class="text-center text-nowrap">
                      @if($resteBordereau > 0)
                        <button type="button"
                          class="btn btn-sm btn-outline-success btn-paiement-bordereau"
                          data-bs-toggle="modal"
                          data-bs-target="#modalPaiementBordereau"
                          data-bordereau-id="{{ $bordereau->id }}"
                          data-bordereau-numero="{{ $bordereau->numero }}"
                          data-bordereau-reste="{{ $resteBordereau }}">
                          <i class="bx bx-money"></i>
                        </button>
                      @endif
                      <a href="{{ route('gestionfinanciere.transporteur.bordereau.pdf', ['transporteur' => $transporteur->id, 'id' => $bordereau->id]) }}" target="_blank" class="btn btn-sm btn-outline-info" title="PDF">
                        <i class="bx bx-printer"></i>
                      </a>
                      <form method="POST" action="{{ route('gestionfinanciere.transporteur.bordereau.destroy', ['transporteur' => $transporteur->id, 'id' => $bordereau->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce bordereau ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center text-muted py-4">Aucun bordereau généré pour ce transporteur</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        @if($fichesSortie->isEmpty())
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h5 class="mb-0"><i class="bx bx-history me-2"></i>Historique des paiements ({{ $historiquePaiements->count() }})</h5>
              <small class="text-muted">Paiements de bordereaux et de fiches enregistrés pour ce transporteur.</small>
            </div>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Bordereau</th>
                  <th>Véhicule</th>
                  <th class="text-end">Montant</th>
                  <th>Observation</th>
                </tr>
              </thead>
              <tbody>
                @forelse($historiquePaiements as $paiement)
                  <tr>
                    <td>{{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                      @if($paiement->bordereau)
                        <span class="badge bg-label-danger">{{ $paiement->bordereau->numero }}</span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td>{{ $paiement->matricule_vehicule ?: '—' }}</td>
                    <td class="text-end fw-semibold text-success">{{ number_format((float) $paiement->montant, 0, ',', ' ') }} FCFA</td>
                    <td class="text-wrap" style="max-width: 420px;">{{ $paiement->observation ?: '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Aucun paiement enregistré pour ce transporteur</td>
                  </tr>
                @endforelse
              </tbody>
              @if($historiquePaiements->isNotEmpty())
                <tfoot>
                  <tr class="fw-bold">
                    <td colspan="3">Total payé</td>
                    <td class="text-end text-success">{{ number_format((float) $historiquePaiements->sum('montant'), 0, ',', ' ') }} FCFA</td>
                    <td></td>
                  </tr>
                </tfoot>
              @endif
            </table>
          </div>
        </div>
        @else
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h5 class="mb-0">Fiches de sortie ({{ $fichesSortie->count() }})</h5>
              <small class="text-muted">Le prix unitaire (PU) est saisi manuellement pour chaque fiche.</small>
            </div>
            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalHistorique">
              <i class="bx bx-history"></i> Historique paiements fiches
            </button>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>N° ticket</th>
                  <th>Usine</th>
                  <th>Agent</th>
                  <th>Véhicule</th>
                  <th>Poids (kg)</th>
                  <th>PU</th>
                  <th class="text-end">Montant</th>
                  <th class="text-end">Avance</th>
                  <th class="text-end">Payé</th>
                  <th class="text-end">Reste</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($fichesSortie as $fiche)
                  @php
                    $poids = $ticketFicheService->poidsEffectif($fiche);
                    $numeroTicket = $ticketFicheService->numeroTicketEffectif($fiche);
                    $nomUsine = $ticketFicheService->usineNomEffectif($fiche);
                    $nomAgent = $ticketFicheService->agentNomEffectif($fiche);
                    $pu = $fiche->prix_unitaire_transport;
                    $montantGlobalFiche = $pu ? ($poids * $pu) : 0;
                    $depensesTableau = \App\Models\Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
                        ->whereDate('date_depense', '>=', $fiche->date_chargement)
                        ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
                        ->sum('montant');
                    $avanceTableau = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0) + $depensesTableau;
                    $montantPayeFiche = $fiche->montant_paye_transporteur ?? 0;
                    $resteAPayerFiche = $montantGlobalFiche - $avanceTableau - $montantPayeFiche;
                  @endphp
                  <tr>
                    <td>{{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</td>
                    <td>
                      @if($numeroTicket)
                        <span class="badge bg-label-info">{{ $numeroTicket }}</span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td>{{ $nomUsine ?: '—' }}</td>
                    <td>{{ $nomAgent ?: '—' }}</td>
                    <td>
                      <a href="{{ route('gestionfinanciere.transporteur.vehicule', ['matricule' => $fiche->matricule_vehicule]) }}" class="fw-bold text-primary text-decoration-none">
                        {{ $fiche->matricule_vehicule }}
                      </a>
                    </td>
                    <td>{{ $poids > 0 ? number_format($poids, 0, ',', ' ') : '—' }}</td>
                    <td>
                      @if($pu !== null && (float) $pu > 0)
                        <span class="fw-semibold text-primary">{{ number_format($pu, 0, ',', ' ') }} FCFA</span>
                      @else
                        <span class="badge bg-label-warning">Non saisi</span>
                      @endif
                    </td>
                    <td class="text-end text-danger">{{ $montantGlobalFiche > 0 ? number_format($montantGlobalFiche, 0, ',', ' ') : '-' }}</td>
                    <td class="text-end text-info">{{ $avanceTableau > 0 ? number_format($avanceTableau, 0, ',', ' ') : '-' }}</td>
                    <td class="text-end text-success">{{ number_format($montantPayeFiche, 0, ',', ' ') }}</td>
                    <td class="text-end {{ $resteAPayerFiche < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ number_format($resteAPayerFiche, 0, ',', ' ') }}</td>
                    <td class="text-nowrap">
                      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalPU{{ $fiche->id }}" title="Saisir le prix unitaire">
                        <i class="bx bx-money me-1"></i>Prix unitaire
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="12" class="text-center text-muted py-4">Aucune fiche de sortie pour ce transporteur</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalHistorique" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Historique des paiements par fiche</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Filtrer par véhicule</label>
          <select id="filtreVehiculeHistorique" class="form-select">
            <option value="">Tous les véhicules</option>
            @foreach($vehicules as $vehicule)
              <option value="{{ $vehicule }}">{{ $vehicule }}</option>
            @endforeach
          </select>
        </div>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Date</th>
                <th>Véhicule</th>
                <th>Montant</th>
                <th>Observation</th>
              </tr>
            </thead>
            <tbody id="historiqueBody">
              <tr><td colspan="4" class="text-center">Chargement...</td></tr>
            </tbody>
            <tfoot>
              <tr class="fw-bold">
                <td colspan="2">Total</td>
                <td id="totalHistorique" class="text-success">0 FCFA</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

@foreach($fichesSortie as $fiche)
  @php
    $poids = $ticketFicheService->poidsEffectif($fiche);
    $numeroTicket = $ticketFicheService->numeroTicketEffectif($fiche);
    $pu = $fiche->prix_unitaire_transport;
    $montantGlobalFiche = $pu ? ($poids * $pu) : 0;
  @endphp

  <div class="modal fade" id="modalPU{{ $fiche->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('gestionfinanciere.transporteur.updatePU', $fiche->id) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Prix unitaire transport</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-light border mb-3">
              <div><strong>Véhicule :</strong> {{ $fiche->matricule_vehicule }}</div>
              <div><strong>Date :</strong> {{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</div>
              <div><strong>Poids :</strong> {{ $poids ? number_format($poids, 0, ',', ' ') . ' kg' : '-' }}</div>
              <div><strong>Ticket :</strong> {{ $numeroTicket ?? '—' }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Prix unitaire (FCFA / kg) <span class="text-danger">*</span></label>
              <input
                type="number"
                name="prix_unitaire"
                class="form-control form-control-lg"
                value="{{ $pu ?? '' }}"
                min="0"
                step="1"
                required
                placeholder="Ex: 150"
              />
              <small class="text-muted">Saisie manuelle — non repris depuis le ticket.</small>
            </div>
            @if($poids > 0)
              <div class="text-muted small">
                Montant calculé : <strong class="text-danger">{{ $pu ? number_format($poids * $pu, 0, ',', ' ') : '—' }} FCFA</strong>
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach

<div class="modal fade" id="modalPaiementBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPaiementBordereau" method="POST" action="">
        @csrf
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement bordereau</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">Bordereau : <strong id="paiementBordereauNumero"></strong></p>
          <p class="mb-3">Reste à payer : <strong id="paiementBordereauReste" class="text-danger"></strong> FCFA</p>
          @if(($montantAvancesTransporteur ?? 0) > 0)
            <div class="alert alert-warning mb-3">
              <i class="bx bx-wallet me-1"></i>
              Avance disponible : <strong>{{ number_format($montantAvancesTransporteur, 0, ',', ' ') }} FCFA</strong>.<br>
              <small>Le paiement sera imputé sur l'avance et ne pourra dépasser ni son solde ni le reste dû du bordereau.</small>
            </div>
          @endif
          <div class="mb-3">
            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <input type="text" name="montant" id="paiementBordereauMontant" class="form-control montant-input-bordereau" required placeholder="Ex: 123 000" inputmode="numeric" autocomplete="off" />
            <small id="paiementBordereauLimite" class="text-muted"></small>
          </div>
          <div class="mb-3">
            <label class="form-label">Date du paiement <span class="text-danger">*</span></label>
            <input type="date" name="date_paiement" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>
          <div class="mb-0">
            <label class="form-label">Observation</label>
            <textarea name="observation" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalGenererBordereau" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-file me-2"></i>Générer un bordereau</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('gestionfinanciere.transporteur.bordereau.store', $transporteur) }}" id="formGenererBordereau">
        @csrf
        <div class="modal-body">
          <p class="text-muted small mb-3">
            Un numéro sera attribué automatiquement
            @if(!empty($exempleNumeroBordereau))
              (ex.&nbsp;: <strong>{{ $exempleNumeroBordereau }}</strong>).
            @endif
            Seules les fiches avec un prix unitaire transport saisi sont éligibles.
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
                <i class="bx bx-search me-1"></i>Charger les fiches
              </button>
            </div>
          </div>
          <div id="bordereauChargement" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Chargement…</p>
          </div>
          <div id="bordereauAucuneFiche" class="alert alert-warning d-none mb-0">
            Aucune fiche éligible sur cette période (prix unitaire requis, fiches déjà bordereau exclues).
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
            <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
              <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="sticky-top bg-white">
                  <tr>
                    <th style="width:40px"><input type="checkbox" id="checkAllFichesBordereau" checked></th>
                    <th>N° fiche</th>
                    <th>N° ticket</th>
                    <th>Date</th>
                    <th>Véhicule</th>
                    <th>Usine</th>
                    <th class="text-end">Poids</th>
                    <th class="text-end">PU</th>
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
@endsection

@section('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  function formatNumber(value) {
    value = value.replace(/\D/g, '');
    return value ? parseInt(value, 10).toLocaleString('fr-FR').replace(/\u202F/g, ' ').replace(/,/g, ' ') : '';
  }

  function chargerHistorique(vehicule) {
    var url = '{{ route("gestionfinanciere.transporteur.historique", $transporteur) }}';
    if (vehicule) {
      url += '?vehicule=' + encodeURIComponent(vehicule);
    }

    fetch(url)
      .then(function(response) { return response.json(); })
      .then(function(data) {
        var tbody = document.getElementById('historiqueBody');
        var total = 0;

        if (!data.paiements.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucun paiement trouvé</td></tr>';
        } else {
          tbody.innerHTML = '';
          data.paiements.forEach(function(paiement) {
            var date = new Date(paiement.date_paiement);
            var montant = parseFloat(paiement.montant);
            total += montant;
            tbody.innerHTML += '<tr><td>' + date.toLocaleDateString('fr-FR') + '</td><td class="fw-bold">' + paiement.matricule_vehicule + '</td><td class="text-success">' + montant.toLocaleString('fr-FR') + ' FCFA</td><td>' + (paiement.observation || '-') + '</td></tr>';
          });
        }

        document.getElementById('totalHistorique').textContent = total.toLocaleString('fr-FR') + ' FCFA';
      });
  }

  var modalHistorique = document.getElementById('modalHistorique');
  if (modalHistorique) {
    modalHistorique.addEventListener('shown.bs.modal', function() {
      chargerHistorique();
    });
  }

  var filtreHistorique = document.getElementById('filtreVehiculeHistorique');
  if (filtreHistorique) {
    filtreHistorique.addEventListener('change', function() {
      chargerHistorique(this.value);
    });
  }

  var urlPaiementBordereauBase = @json(url('/gestion-financiere/transporteur/' . $transporteur->id . '/bordereaux'));
  var avanceDisponible = @json((int) ($montantAvancesTransporteur ?? 0));
  var inputPaiementBordereau = document.getElementById('paiementBordereauMontant');

  function appliquerLimiteMontantPaiement(input) {
    if (!input) return;
    var digits = String(input.value || '').replace(/\D/g, '');
    var limite = parseInt(input.dataset.max || '0', 10);
    var montant = digits ? parseInt(digits, 10) : 0;

    if (limite > 0 && montant > limite) {
      montant = limite;
    }

    input.value = montant > 0 ? montant.toLocaleString('fr-FR') : '';
  }

  if (inputPaiementBordereau) {
    inputPaiementBordereau.addEventListener('input', function() {
      appliquerLimiteMontantPaiement(this);
    });
    inputPaiementBordereau.addEventListener('blur', function() {
      appliquerLimiteMontantPaiement(this);
    });
    inputPaiementBordereau.addEventListener('paste', function(event) {
      event.preventDefault();
      var texte = (event.clipboardData || window.clipboardData).getData('text') || '';
      this.value = texte;
      appliquerLimiteMontantPaiement(this);
    });
  }

  document.querySelectorAll('.btn-paiement-bordereau').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.getAttribute('data-bordereau-id');
      var numero = btn.getAttribute('data-bordereau-numero');
      var reste = parseInt(btn.getAttribute('data-bordereau-reste') || '0', 10);
      var limite = avanceDisponible > 0 ? Math.min(reste, avanceDisponible) : reste;
      document.getElementById('formPaiementBordereau').action = urlPaiementBordereauBase + '/' + id + '/paiement';
      document.getElementById('paiementBordereauNumero').textContent = numero;
      document.getElementById('paiementBordereauReste').textContent = reste.toLocaleString('fr-FR');
      if (inputPaiementBordereau) {
        inputPaiementBordereau.dataset.max = String(limite);
        inputPaiementBordereau.value = limite > 0 ? limite.toLocaleString('fr-FR') : '';
      }
      document.getElementById('paiementBordereauLimite').textContent =
        'Maximum autorisé : ' + limite.toLocaleString('fr-FR') + ' FCFA';
    });
  });

  var urlFichesBordereau = @json(route('gestionfinanciere.transporteur.bordereau.fiches', $transporteur));
  var tbodyBordereau = document.getElementById('tbodyFichesBordereau');
  var listeBlock = document.getElementById('bordereauListeFiches');
  var aucuneBlock = document.getElementById('bordereauAucuneFiche');
  var chargementBlock = document.getElementById('bordereauChargement');
  var btnSubmitBordereau = document.getElementById('btnSubmitBordereau');
  var nbSel = document.getElementById('bordereauNbSelection');
  var montantSel = document.getElementById('bordereauMontantSelection');

  function majSelectionBordereau() {
    if (!tbodyBordereau) return;
    var checks = tbodyBordereau.querySelectorAll('.fiche-bordereau-check:checked');
    var total = 0;
    checks.forEach(function(c) { total += parseInt(c.getAttribute('data-montant') || '0', 10); });
    if (nbSel) nbSel.textContent = checks.length + ' fiche(s)';
    if (montantSel) montantSel.textContent = total.toLocaleString('fr-FR') + ' FCFA';
    if (btnSubmitBordereau) btnSubmitBordereau.disabled = checks.length === 0;
  }

  function renderFichesBordereau(fiches) {
    if (!tbodyBordereau) return;
    tbodyBordereau.innerHTML = '';
    fiches.forEach(function(f) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="checkbox" class="form-check-input fiche-bordereau-check" name="fiche_ids[]" value="' + f.fiche_id + '" data-montant="' + f.montant + '" checked></td>' +
        '<td><small>' + (f.numero_fiche || ('#' + f.fiche_id)) + '</small></td>' +
        '<td><small>' + (f.numero_ticket || '—') + '</small></td>' +
        '<td><small>' + (f.date_chargement || '—') + '</small></td>' +
        '<td>' + (f.matricule_vehicule || '—') + '</td>' +
        '<td><small>' + (f.usine || '—') + '</small></td>' +
        '<td class="text-end">' + Number(f.poids || 0).toLocaleString('fr-FR') + '</td>' +
        '<td class="text-end">' + Number(f.prix_unitaire || 0).toLocaleString('fr-FR') + '</td>' +
        '<td class="text-end text-danger">' + Number(f.montant || 0).toLocaleString('fr-FR') + '</td>';
      tbodyBordereau.appendChild(tr);
    });
    tbodyBordereau.querySelectorAll('.fiche-bordereau-check').forEach(function(c) {
      c.addEventListener('change', majSelectionBordereau);
    });
    majSelectionBordereau();
  }

  var btnCharger = document.getElementById('btnChargerFichesBordereau');
  if (btnCharger) {
    btnCharger.addEventListener('click', function() {
      var debut = document.getElementById('bordereau_date_debut').value;
      var fin = document.getElementById('bordereau_date_fin').value;
      if (!debut || !fin) return;
      if (listeBlock) listeBlock.classList.add('d-none');
      if (aucuneBlock) aucuneBlock.classList.add('d-none');
      if (chargementBlock) chargementBlock.classList.remove('d-none');
      fetch(urlFichesBordereau + '?date_debut=' + encodeURIComponent(debut) + '&date_fin=' + encodeURIComponent(fin))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (chargementBlock) chargementBlock.classList.add('d-none');
          if (!data.fiches || !data.fiches.length) {
            if (aucuneBlock) aucuneBlock.classList.remove('d-none');
            if (btnSubmitBordereau) btnSubmitBordereau.disabled = true;
            return;
          }
          renderFichesBordereau(data.fiches);
          if (listeBlock) listeBlock.classList.remove('d-none');
        })
        .catch(function() {
          if (chargementBlock) chargementBlock.classList.add('d-none');
          if (aucuneBlock) aucuneBlock.classList.remove('d-none');
        });
    });
  }

  var checkAll = document.getElementById('checkAllFichesBordereau');
  if (checkAll) {
    checkAll.addEventListener('change', function() {
      if (!tbodyBordereau) return;
      tbodyBordereau.querySelectorAll('.fiche-bordereau-check').forEach(function(c) {
        c.checked = checkAll.checked;
      });
      majSelectionBordereau();
    });
  }

  var btnToutSel = document.getElementById('btnToutSelectionnerBordereau');
  if (btnToutSel) {
    btnToutSel.addEventListener('click', function() {
      if (!tbodyBordereau) return;
      tbodyBordereau.querySelectorAll('.fiche-bordereau-check').forEach(function(c) { c.checked = true; });
      if (checkAll) checkAll.checked = true;
      majSelectionBordereau();
    });
  }

  var btnToutDesel = document.getElementById('btnToutDeselectionnerBordereau');
  if (btnToutDesel) {
    btnToutDesel.addEventListener('click', function() {
      if (!tbodyBordereau) return;
      tbodyBordereau.querySelectorAll('.fiche-bordereau-check').forEach(function(c) { c.checked = false; });
      if (checkAll) checkAll.checked = false;
      majSelectionBordereau();
    });
  }

  var formBordereau = document.getElementById('formGenererBordereau');
  if (formBordereau) {
    formBordereau.addEventListener('submit', function(e) {
      if (!tbodyBordereau || tbodyBordereau.querySelectorAll('.fiche-bordereau-check:checked').length === 0) {
        e.preventDefault();
        alert('Sélectionnez au moins une fiche.');
      }
    });
  }

  var formPaiementBordereau = document.getElementById('formPaiementBordereau');
  if (formPaiementBordereau) {
    formPaiementBordereau.addEventListener('submit', function(event) {
      var input = document.getElementById('paiementBordereauMontant');
      if (input) {
        var montant = parseInt(input.value.replace(/\s/g, ''), 10) || 0;
        var limite = parseInt(input.dataset.max || '0', 10);
        if (montant > limite) {
          event.preventDefault();
          alert('Le montant ne peut pas dépasser ' + limite.toLocaleString('fr-FR') + ' FCFA.');
          return;
        }
        input.value = String(montant);
      }
    });
  }
});
</script>
@endsection
