@extends('layout.main')

@section('content')
<style>
  .tf-fin-page .tf-tabs {
    display: inline-flex;
    gap: 0.35rem;
    padding: 0.35rem;
    background: #f2f3f8;
    border-radius: 0.55rem;
  }
  .tf-fin-page .tf-tabs .nav-link {
    border: 0;
    border-radius: 0.4rem;
    color: #6f6b7d;
    font-weight: 500;
    padding: 0.55rem 1.1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }
  .tf-fin-page .tf-tabs .nav-link:hover {
    color: #7367f0;
    background: rgba(115, 103, 240, 0.08);
  }
  .tf-fin-page .tf-tabs .nav-link.active {
    color: #fff;
    background: #7367f0;
    box-shadow: 0 2px 6px rgba(115, 103, 240, 0.35);
  }
  .tf-fin-page .tf-search {
    background: #f8f8fa;
    border: 1px solid #ebebed;
    border-radius: 0.5rem;
    padding: 0.85rem 1rem;
  }
  .tf-fin-page .tf-table {
    font-size: 0.82rem;
  }
  .tf-fin-page .tf-table thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #6f6b7d;
    background: #f8f8fa;
    padding: 0.55rem 0.65rem;
    white-space: nowrap;
  }
  .tf-fin-page .tf-table td {
    padding: 0.55rem 0.65rem;
    vertical-align: middle;
  }
</style>

<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y tf-fin-page">
    <div class="mb-4">
      <h4 class="mb-1">Gestion Financière des transferts</h4>
      <p class="text-muted mb-0">Suivi des montants dus, payés et restes par usine ou particulier</p>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
          <nav class="tf-tabs" aria-label="Type de client">
            <a class="nav-link {{ $tab === 'usines' ? 'active' : '' }}" href="{{ route('transferts.financier.index', ['tab' => 'usines']) }}">
              <i class="bx bx-buildings"></i> Usines
            </a>
            <a class="nav-link {{ $tab === 'particuliers' ? 'active' : '' }}" href="{{ route('transferts.financier.index', ['tab' => 'particuliers']) }}">
              <i class="bx bx-user"></i> Particuliers
            </a>
          </nav>
          <span class="badge bg-label-primary">{{ $rows->count() }} {{ $tab === 'usines' ? 'usine(s)' : 'particulier(s)' }}</span>
        </div>

        <form method="GET" action="{{ route('transferts.financier.index') }}" class="tf-search mb-4">
          <input type="hidden" name="tab" value="{{ $tab }}" />
          <div class="row g-2 align-items-center">
            <div class="col-md-7 col-lg-6">
              <div class="input-group">
                <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                <input
                  type="text"
                  name="search"
                  class="form-control"
                  value="{{ $search }}"
                  placeholder="{{ $tab === 'usines' ? 'Rechercher une usine...' : 'Rechercher un particulier (nom, code)...' }}"
                />
              </div>
            </div>
            <div class="col-auto d-flex gap-2">
              <button type="submit" class="btn btn-primary">Rechercher</button>
              <a href="{{ route('transferts.financier.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">Réinit.</a>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover tf-table mb-0">
            <thead>
              <tr>
                <th>{{ $tab === 'usines' ? 'Usine' : 'Particulier' }}</th>
                <th class="text-end">Montant dû</th>
                <th class="text-end">Montant payé</th>
                <th class="text-end">Reste à payer</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rows as $item)
                @php
                  $lien = route('transferts.financier.show', ['type' => $item['client_type'], 'id' => $item['client_id']]);
                @endphp
                <tr>
                  <td>
                    <a href="{{ $lien }}" class="text-primary fw-bold">
                      {{ $item['client'] ?: '—' }}
                    </a>
                    @if(!empty($item['code']))
                      <br><small class="text-muted">{{ $item['code'] }}</small>
                    @else
                      <br><small class="text-muted">{{ $item['nb_transferts'] }} transfert(s)</small>
                    @endif
                  </td>
                  <td class="text-end">
                    <span class="text-primary fw-bold">{{ number_format($item['montant_du'], 0, ',', ' ') }} FCFA</span>
                  </td>
                  <td class="text-end">
                    <span class="text-success fw-semibold">{{ number_format($item['montant_paye'], 0, ',', ' ') }} FCFA</span>
                  </td>
                  <td class="text-end">
                    @if($item['reste_a_payer'] > 0)
                      <span class="text-danger fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                    @elseif($item['reste_a_payer'] < 0)
                      <span class="text-warning fw-bold">{{ number_format($item['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                    @else
                      <span class="text-success"><i class="bx bx-check-circle"></i> Soldé</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <a href="{{ $lien }}" class="btn btn-sm btn-outline-success" title="Détail des transferts">
                      <i class="bx bx-list-ul"></i> Transferts
                    </a>
                    <a href="{{ $lien }}" class="btn btn-sm btn-outline-primary" title="Voir">
                      <i class="bx bx-show"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    Aucun {{ $tab === 'usines' ? 'usine' : 'particulier' }} trouvé
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
