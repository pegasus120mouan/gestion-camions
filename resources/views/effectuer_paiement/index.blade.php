@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h4 class="mb-1">Effectuer un paiement</h4>
        <p class="text-muted mb-0">Enregistrez un paiement directement depuis chaque onglet.</p>
      </div>
      <div class="d-flex gap-3 text-end">
        <div>
          <div class="text-muted small">À payer</div>
          <div class="fw-bold">{{ number_format($stats['a_payer'], 0, ',', ' ') }}</div>
        </div>
        <div>
          <div class="text-muted small">Reste total</div>
          <div class="fw-bold text-danger">{{ number_format($stats['reste_total'], 0, ',', ' ') }} FCFA</div>
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error') || $errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') ?: $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    {{-- Onglets horizontaux --}}
    <ul class="nav nav-pills mb-3 flex-nowrap overflow-auto">
      <li class="nav-item">
        <a class="nav-link {{ $onglet === 'agents' ? 'active' : '' }}"
          href="{{ route('effectuer_paiement.index', ['onglet' => 'agents']) }}">
          <i class="bx bx-file me-1"></i>Bordereaux
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $onglet === 'transporteurs' ? 'active' : '' }}"
          href="{{ route('effectuer_paiement.index', ['onglet' => 'transporteurs']) }}">
          <i class="bx bx-bus me-1"></i>Transporteurs
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $onglet === 'fournisseurs' ? 'active' : '' }}"
          href="{{ route('effectuer_paiement.index', ['onglet' => 'fournisseurs']) }}">
          <i class="bx bx-store me-1"></i>Fournisseurs
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $onglet === 'salaires' ? 'active' : '' }}"
          href="{{ route('effectuer_paiement.index', ['onglet' => 'salaires']) }}">
          <i class="bx bx-wallet me-1"></i>Salaires
        </a>
      </li>
    </ul>

    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('effectuer_paiement.index') }}" class="row g-2 align-items-end">
          <input type="hidden" name="onglet" value="{{ $onglet }}">
          <div class="col-md-4 col-lg-3">
            <label class="form-label small text-uppercase text-muted">Recherche</label>
            <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}"
              placeholder="@if($onglet === 'agents') N° bordereau, agent… @elseif($onglet === 'transporteurs') N° bordereau, transporteur… @elseif($onglet === 'fournisseurs') Nom du fournisseur… @else Chauffeur, camion… @endif">
          </div>
          <div class="col-md-3 col-lg-2">
            <label class="form-label small text-uppercase text-muted">Statut</label>
            <select name="statut" class="form-select form-select-sm">
              <option value="a_payer" @selected($filters['statut'] === 'a_payer')>À payer</option>
              <option value="soldes" @selected($filters['statut'] === 'soldes')>Soldés</option>
              <option value="tous" @selected($filters['statut'] === 'tous')>Tous</option>
            </select>
          </div>
          @if($onglet === 'salaires')
            <div class="col-md-2 col-lg-2">
              <label class="form-label small text-uppercase text-muted">Mois</label>
              <select name="mois" class="form-select form-select-sm">
                @for($m = 1; $m <= 12; $m++)
                  <option value="{{ $m }}" @selected($salairesMois === $m)>
                    {{ \Carbon\Carbon::createFromDate($salairesAnnee, $m, 1)->locale('fr')->translatedFormat('F') }}
                  </option>
                @endfor
              </select>
            </div>
            <div class="col-md-2 col-lg-2">
              <label class="form-label small text-uppercase text-muted">Année</label>
              <select name="annee" class="form-select form-select-sm">
                @for($a = now()->year + 1; $a >= now()->year - 3; $a--)
                  <option value="{{ $a }}" @selected($salairesAnnee === $a)>{{ $a }}</option>
                @endfor
              </select>
            </div>
          @endif
          <div class="col-md-3 col-lg-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bx bx-search me-1"></i>Filtrer
            </button>
            <a href="{{ route('effectuer_paiement.index', ['onglet' => $onglet]) }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
          </div>
        </form>
      </div>
    </div>

    @if($onglet === 'agents')
    {{-- ============ Onglet Bordereaux agents ============ --}}
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bx bx-file me-1"></i>Bordereaux agents</h6>
        <span class="badge bg-label-secondary">{{ $bordereaux->total() }} bordereau(x)</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>N° bordereau</th>
                <th>Agent</th>
                <th>Date</th>
                <th class="text-end">Total</th>
                <th class="text-end">Payé</th>
                <th class="text-end">Reste</th>
                <th class="text-end">Financement</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($bordereaux as $bordereau)
                @php
                  $reste = (int) round($bordereau->reste_a_payer);
                  $idAgent = (int) $bordereau->id_agent;
                  $financement = (int) ($financements[$idAgent] ?? 0);
                @endphp
                <tr>
                  <td>
                    <a href="{{ route('gestionfinanciere.agent.bordereau.pdf', ['id_agent' => $idAgent, 'id' => $bordereau->id]) }}"
                      target="_blank" class="fw-semibold text-primary text-decoration-none">
                      {{ $bordereau->numero }}
                    </a>
                  </td>
                  <td>
                    <a href="{{ route('gestionfinanciere.agent.show', ['id_agent' => $idAgent]) }}">
                      {{ $bordereau->agent_nom ?: ('Agent #'.$idAgent) }}
                    </a>
                    @if($bordereau->agent_numero)
                      <div class="small text-muted">{{ $bordereau->agent_numero }}</div>
                    @endif
                  </td>
                  <td>{{ $bordereau->date_generation?->format('d/m/Y') ?? '—' }}</td>
                  <td class="text-end">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }}</td>
                  <td class="text-end text-success">{{ number_format((float) ($bordereau->montant_paye ?? 0), 0, ',', ' ') }}</td>
                  <td class="text-end fw-semibold {{ $reste > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($reste, 0, ',', ' ') }}
                  </td>
                  <td class="text-end">
                    @if($financement > 0)
                      <span class="badge bg-label-warning">{{ number_format($financement, 0, ',', ' ') }}</span>
                    @else
                      <span class="text-muted">0</span>
                    @endif
                  </td>
                  <td class="text-end">
                    @if($reste > 0)
                      <button type="button"
                        class="btn btn-sm btn-success btn-paiement-bordereau"
                        data-bs-toggle="modal"
                        data-bs-target="#modalPaiementBordereau"
                        data-agent-id="{{ $idAgent }}"
                        data-bordereau-id="{{ $bordereau->id }}"
                        data-bordereau-numero="{{ $bordereau->numero }}"
                        data-bordereau-reste="{{ $reste }}"
                        data-financement="{{ $financement }}">
                        <i class="bx bx-money me-1"></i>Payer
                      </button>
                    @else
                      <span class="badge bg-label-success">Soldé</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-5">
                    <i class="bx bx-file fs-1 d-block mb-2 opacity-25"></i>
                    Aucun bordereau à afficher.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($bordereaux->hasPages())
          <div class="mt-3">
            {{ $bordereaux->links() }}
          </div>
        @endif
      </div>
    </div>

    @elseif($onglet === 'transporteurs')
    {{-- ============ Onglet Transporteurs ============ --}}
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bx bx-bus me-1"></i>Bordereaux transporteurs</h6>
        <span class="badge bg-label-secondary">{{ $bordereauxTransporteur->total() }} bordereau(x)</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>N° bordereau</th>
                <th>Transporteur</th>
                <th>Date</th>
                <th class="text-end">Total</th>
                <th class="text-end">Payé</th>
                <th class="text-end">Reste</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($bordereauxTransporteur as $bordereau)
                @php $reste = (int) round($bordereau->reste_a_payer); @endphp
                <tr>
                  <td>
                    <a href="{{ route('gestionfinanciere.transporteur.bordereau.pdf', ['transporteur' => $bordereau->transporteur_id, 'id' => $bordereau->id]) }}"
                      target="_blank" class="fw-semibold text-primary text-decoration-none">
                      {{ $bordereau->numero }}
                    </a>
                  </td>
                  <td>
                    <a href="{{ route('gestionfinanciere.transporteur.show', $bordereau->transporteur_id) }}">
                      {{ $bordereau->transporteur_nom ?: ('Transporteur #'.$bordereau->transporteur_id) }}
                    </a>
                    @if($bordereau->transporteur_code)
                      <div class="small text-muted">{{ $bordereau->transporteur_code }}</div>
                    @endif
                  </td>
                  <td>{{ $bordereau->date_generation?->format('d/m/Y') ?? '—' }}</td>
                  <td class="text-end">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }}</td>
                  <td class="text-end text-success">{{ number_format((float) ($bordereau->montant_paye ?? 0), 0, ',', ' ') }}</td>
                  <td class="text-end fw-semibold {{ $reste > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($reste, 0, ',', ' ') }}
                  </td>
                  <td class="text-end">
                    @if($reste > 0)
                      <button type="button"
                        class="btn btn-sm btn-success btn-paiement-transporteur"
                        data-bs-toggle="modal"
                        data-bs-target="#modalPaiementTransporteur"
                        data-transporteur-id="{{ $bordereau->transporteur_id }}"
                        data-bordereau-id="{{ $bordereau->id }}"
                        data-bordereau-numero="{{ $bordereau->numero }}"
                        data-bordereau-reste="{{ $reste }}">
                        <i class="bx bx-money me-1"></i>Payer
                      </button>
                    @else
                      <span class="badge bg-label-success">Soldé</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-5">
                    <i class="bx bx-bus fs-1 d-block mb-2 opacity-25"></i>
                    Aucun bordereau transporteur à afficher.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($bordereauxTransporteur->hasPages())
          <div class="mt-3">
            {{ $bordereauxTransporteur->links() }}
          </div>
        @endif
      </div>
    </div>

    @elseif($onglet === 'fournisseurs')
    {{-- ============ Onglet Fournisseurs ============ --}}
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bx bx-store me-1"></i>Fournisseurs</h6>
        <span class="badge bg-label-secondary">{{ $fournisseursData->count() }} fournisseur(s)</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Fournisseur</th>
                <th>Service</th>
                <th class="text-end">Montant dû</th>
                <th class="text-end">Payé</th>
                <th class="text-end">Reste</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($fournisseursData as $ligne)
                @php
                  $fournisseur = $ligne['fournisseur'];
                  $reste = (int) round($ligne['reste_a_payer']);
                @endphp
                <tr>
                  <td>
                    <a href="{{ route('gestionfinanciere.fournisseur.show', ['nom' => $fournisseur->nom]) }}"
                      class="fw-semibold text-primary text-decoration-none">
                      {{ $fournisseur->nom }}
                    </a>
                  </td>
                  <td>{{ $fournisseur->service?->nom_service ?? '—' }}</td>
                  <td class="text-end">{{ number_format((float) $ligne['montant_du'], 0, ',', ' ') }}</td>
                  <td class="text-end text-success">{{ number_format((float) $ligne['montant_paye'], 0, ',', ' ') }}</td>
                  <td class="text-end fw-semibold {{ $reste > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($reste, 0, ',', ' ') }}
                  </td>
                  <td class="text-end">
                    @if($reste > 0)
                      <button type="button"
                        class="btn btn-sm btn-success btn-paiement-fournisseur"
                        data-bs-toggle="modal"
                        data-bs-target="#modalPaiementFournisseur"
                        data-fournisseur-id="{{ $fournisseur->id }}"
                        data-fournisseur-nom="{{ $fournisseur->nom }}"
                        data-fournisseur-reste="{{ $reste }}">
                        <i class="bx bx-money me-1"></i>Payer
                      </button>
                    @else
                      <span class="badge bg-label-success">Soldé</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-5">
                    <i class="bx bx-store fs-1 d-block mb-2 opacity-25"></i>
                    Aucun fournisseur à afficher.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    @else
    {{-- ============ Onglet Salaires ============ --}}
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bx bx-wallet me-1"></i>Salaires chauffeurs — {{ ucfirst($salairesLibelleMois) }}</h6>
        <span class="badge bg-label-secondary">{{ $salairesLignes->count() }} chauffeur(s)</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Chauffeur</th>
                <th>Camion</th>
                <th class="text-end">Salaire</th>
                <th class="text-end">Avances</th>
                <th class="text-end">Payé</th>
                <th class="text-end">Reste</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($salairesLignes as $ligne)
                @php
                  $chauffeur = $ligne['chauffeur'];
                  $soldes = $ligne['soldes'];
                  $reste = (int) round($soldes['reste']);
                  $lienShow = route('gestionfinanciere.chauffeurs_salaires.show', [
                    'chauffeur' => $chauffeur->id,
                    'annee' => $salairesAnnee,
                    'mois' => $salairesMois,
                  ]);
                @endphp
                <tr>
                  <td>
                    <a href="{{ $lienShow }}" class="fw-semibold text-primary text-decoration-none">
                      {{ trim($chauffeur->nom . ' ' . $chauffeur->prenoms) ?: '—' }}
                    </a>
                    @if($chauffeur->contact)
                      <div class="small text-muted">{{ $chauffeur->contact }}</div>
                    @endif
                  </td>
                  <td>
                    @if($chauffeur->matricule_vehicule)
                      <span class="badge bg-label-secondary">{{ $chauffeur->matricule_vehicule }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-end">{{ number_format($soldes['du'], 0, ',', ' ') }}</td>
                  <td class="text-end text-info">{{ number_format($soldes['avances'] + (float) ($soldes['avances_reportees'] ?? 0), 0, ',', ' ') }}</td>
                  <td class="text-end text-success">{{ number_format($soldes['paye'], 0, ',', ' ') }}</td>
                  <td class="text-end fw-semibold {{ $reste > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($reste, 0, ',', ' ') }}
                  </td>
                  <td class="text-end">
                    @if($reste > 0)
                      <a href="{{ $lienShow }}" class="btn btn-sm btn-success">
                        <i class="bx bx-money me-1"></i>Payer
                      </a>
                    @else
                      <span class="badge bg-label-success">Soldé</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-5">
                    <i class="bx bx-wallet fs-1 d-block mb-2 opacity-25"></i>
                    Aucun chauffeur à afficher.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif
  </div>
</div>

{{-- Modal paiement bordereau agent --}}
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
          <div class="alert alert-info mb-2">
            <strong>Reste à payer :</strong> <span id="paiementBordereauReste">0</span> FCFA
          </div>
          <div class="alert alert-warning mb-2 d-none" id="paiementFinancementAlert">
            <strong>Financement disponible :</strong> <span id="paiementFinancementMontant">0</span> FCFA
            <br><small>Le paiement sera déduit du financement de l’agent.</small>
          </div>
          <div class="alert alert-success mb-2 d-none" id="paiementCaisseLocaleAlert">
            <strong>Caisse locale :</strong> <span id="paiementCaisseLocaleMontant">{{ number_format((int) $soldeCaisseLocale, 0, ',', ' ') }}</span> FCFA
            <br><small>Pas de financement agent — le paiement sera débité de la caisse locale.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="paiementBordereauMontant" class="form-control" required
              placeholder="Ex: 500 000" inputmode="numeric" autocomplete="off" />
            <small class="text-muted" id="paiementBordereauMontantHint">Montant maximum selon le reste et la source.</small>
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
          <div class="mb-0">
            <label class="form-label">Commentaire</label>
            <input type="text" name="commentaire" class="form-control" placeholder="Optionnel" />
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

{{-- Modal paiement bordereau transporteur --}}
<div class="modal fade" id="modalPaiementTransporteur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement transporteur</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formPaiementTransporteur" method="POST" action="">
        @csrf
        <div class="modal-body">
          <div class="alert alert-secondary mb-2">
            <strong>Bordereau :</strong> <span id="paiementTransporteurNumero">—</span>
          </div>
          <div class="alert alert-info mb-2">
            <strong>Reste à payer :</strong> <span id="paiementTransporteurReste">0</span> FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="paiementTransporteurMontant" class="form-control" required
              placeholder="Ex: 500 000" inputmode="numeric" autocomplete="off" />
            <small class="text-muted">Maximum : le reste à payer du bordereau.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-0">
            <label class="form-label">Observation</label>
            <input type="text" name="observation" class="form-control" placeholder="Optionnel" />
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

{{-- Modal paiement fournisseur --}}
<div class="modal fade" id="modalPaiementFournisseur" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title text-white"><i class="bx bx-money me-2"></i>Paiement fournisseur</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('gestionfinanciere.montant_fournisseur.paiement') }}" id="formPaiementFournisseur">
        @csrf
        <input type="hidden" name="fournisseur_id" id="paiementFournisseurId" value="">
        <div class="modal-body">
          <div class="alert alert-secondary mb-2">
            <strong>Fournisseur :</strong> <span id="paiementFournisseurNom">—</span>
          </div>
          <div class="alert alert-info mb-2">
            <strong>Reste à payer :</strong> <span id="paiementFournisseurReste">0</span> FCFA
          </div>
          <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="paiementFournisseurMontant" class="form-control" required
              placeholder="Ex: 500 000" inputmode="numeric" autocomplete="off" />
          </div>
          <div class="mb-3">
            <label class="form-label">Date de paiement</label>
            <input type="date" name="date_paiement" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select name="mode_paiement" class="form-select" required>
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
          <div class="mb-0">
            <label class="form-label">Commentaire</label>
            <input type="text" name="commentaire" class="form-control" placeholder="Optionnel" />
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  function formatNombre(n) {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function formatMontantSaisie(digits) {
    digits = String(digits || '').replace(/\D/g, '');
    if (!digits) return '';
    return formatNombre(parseInt(digits, 10));
  }

  function brancherMontant(input, getPlafond) {
    if (!input) return;
    input.addEventListener('input', function () {
      var digits = String(input.value || '').replace(/\D/g, '');
      var plafond = getPlafond();
      if (digits && plafond > 0 && parseInt(digits, 10) > plafond) {
        digits = String(plafond);
      }
      input.value = formatMontantSaisie(digits);
    });
  }

  function brancherSubmit(form, input, getPlafond) {
    if (!form) return;
    form.addEventListener('submit', function (e) {
      var plafond = getPlafond();
      var montant = parseInt(String(input.value || '').replace(/\D/g, '') || '0', 10);
      if (plafond > 0 && montant > plafond) {
        e.preventDefault();
        alert('Le montant ne peut pas dépasser ' + formatNombre(plafond) + ' FCFA.');
        input.value = formatMontantSaisie(String(plafond));
        return;
      }
      input.value = String(montant || '');
    });
  }

  // ---- Bordereaux agents ----
  var form = document.getElementById('formPaiementBordereau');
  var inputMontant = document.getElementById('paiementBordereauMontant');
  var hint = document.getElementById('paiementBordereauMontantHint');
  var alertFinancement = document.getElementById('paiementFinancementAlert');
  var alertCaisse = document.getElementById('paiementCaisseLocaleAlert');
  var resteCourant = 0;
  var financementCourant = 0;
  var soldeCaisseLocale = {{ (int) $soldeCaisseLocale }};

  function calculerPlafondAgent() {
    if (financementCourant > 0) {
      return resteCourant > 0 ? Math.min(resteCourant, financementCourant) : financementCourant;
    }
    return resteCourant > 0
      ? Math.min(resteCourant, Math.max(0, soldeCaisseLocale))
      : Math.max(0, soldeCaisseLocale);
  }

  function appliquerPlafondAgent() {
    var plafond = calculerPlafondAgent();
    if (financementCourant > 0) {
      alertFinancement.classList.remove('d-none');
      alertCaisse.classList.add('d-none');
      document.getElementById('paiementFinancementMontant').textContent = formatNombre(financementCourant);
      hint.textContent = 'Maximum : ' + formatNombre(plafond) + ' FCFA (plafonné par le financement).';
    } else {
      alertFinancement.classList.add('d-none');
      alertCaisse.classList.remove('d-none');
      document.getElementById('paiementCaisseLocaleMontant').textContent = formatNombre(soldeCaisseLocale);
      hint.textContent = 'Maximum : ' + formatNombre(plafond) + ' FCFA (plafonné par la caisse locale).';
    }
    inputMontant.value = plafond > 0 ? formatMontantSaisie(String(plafond)) : '';
  }

  brancherMontant(inputMontant, calculerPlafondAgent);
  brancherSubmit(form, inputMontant, calculerPlafondAgent);

  document.querySelectorAll('.btn-paiement-bordereau').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var agentId = btn.getAttribute('data-agent-id');
      var bordereauId = btn.getAttribute('data-bordereau-id');
      var numero = btn.getAttribute('data-bordereau-numero');
      resteCourant = parseInt(btn.getAttribute('data-bordereau-reste') || '0', 10);
      financementCourant = parseInt(btn.getAttribute('data-financement') || '0', 10);

      form.action = '{{ url('/gestion-financiere/agent-financier') }}/' + agentId + '/bordereaux/' + bordereauId + '/paiement';
      document.getElementById('paiementBordereauNumero').textContent = numero || '—';
      document.getElementById('paiementBordereauReste').textContent = formatNombre(resteCourant);
      appliquerPlafondAgent();
    });
  });

  // ---- Bordereaux transporteurs ----
  var formTransporteur = document.getElementById('formPaiementTransporteur');
  var inputTransporteur = document.getElementById('paiementTransporteurMontant');
  var resteTransporteur = 0;

  brancherMontant(inputTransporteur, function () { return resteTransporteur; });
  brancherSubmit(formTransporteur, inputTransporteur, function () { return resteTransporteur; });

  document.querySelectorAll('.btn-paiement-transporteur').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var transporteurId = btn.getAttribute('data-transporteur-id');
      var bordereauId = btn.getAttribute('data-bordereau-id');
      var numero = btn.getAttribute('data-bordereau-numero');
      resteTransporteur = parseInt(btn.getAttribute('data-bordereau-reste') || '0', 10);

      formTransporteur.action = '{{ url('/gestion-financiere/transporteur') }}/' + transporteurId + '/bordereaux/' + bordereauId + '/paiement';
      document.getElementById('paiementTransporteurNumero').textContent = numero || '—';
      document.getElementById('paiementTransporteurReste').textContent = formatNombre(resteTransporteur);
      inputTransporteur.value = resteTransporteur > 0 ? formatMontantSaisie(String(resteTransporteur)) : '';
    });
  });

  // ---- Fournisseurs ----
  var formFournisseur = document.getElementById('formPaiementFournisseur');
  var inputFournisseur = document.getElementById('paiementFournisseurMontant');
  var resteFournisseur = 0;

  brancherMontant(inputFournisseur, function () { return resteFournisseur; });
  brancherSubmit(formFournisseur, inputFournisseur, function () { return resteFournisseur; });

  document.querySelectorAll('.btn-paiement-fournisseur').forEach(function (btn) {
    btn.addEventListener('click', function () {
      resteFournisseur = parseInt(btn.getAttribute('data-fournisseur-reste') || '0', 10);
      document.getElementById('paiementFournisseurId').value = btn.getAttribute('data-fournisseur-id') || '';
      document.getElementById('paiementFournisseurNom').textContent = btn.getAttribute('data-fournisseur-nom') || '—';
      document.getElementById('paiementFournisseurReste').textContent = formatNombre(resteFournisseur);
      inputFournisseur.value = resteFournisseur > 0 ? formatMontantSaisie(String(resteFournisseur)) : '';
    });
  });
});
</script>
@endsection
