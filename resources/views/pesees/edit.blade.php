@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Modifier une pesée</h4>
      <a href="{{ route('pesees.index') }}" class="btn btn-outline-secondary">Retour</a>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('pesees.update', $pesee) }}">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Pont</label>
              <select name="pont_pesage_id" class="form-select" required>
                @foreach($ponts as $pont)
                  <option value="{{ $pont->id }}" {{ (string) old('pont_pesage_id', $pesee->pont_pesage_id) === (string) $pont->id ? 'selected' : '' }}>
                    {{ $pont->code }} - {{ $pont->nom }}
                  </option>
                @endforeach
              </select>
              @error('pont_pesage_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Camion</label>
              <select name="camion_id" class="form-select" required>
                @foreach($camions as $camion)
                  <option value="{{ $camion->id }}" {{ (string) old('camion_id', $pesee->camion_id) === (string) $camion->id ? 'selected' : '' }}>
                    {{ $camion->immatriculation }}
                  </option>
                @endforeach
              </select>
              @error('camion_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Produit</label>
              <select name="produit_id" class="form-select" required>
                @foreach($produits as $prod)
                  <option value="{{ $prod->id }}" {{ (string) old('produit_id', $pesee->produit_id) === (string) $prod->id ? 'selected' : '' }}>
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
                @foreach($agents as $a)
                  <option value="{{ $a->id }}" {{ (string) old('agent_id', $pesee->agent_id) === (string) $a->id ? 'selected' : '' }}>
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
                  <option value="{{ $ch->id }}" {{ (string) old('chauffeur_id', $pesee->chauffeur_id) === (string) $ch->id ? 'selected' : '' }}>
                    {{ $ch->name }} {{ $ch->prenom }} ({{ $ch->login }})
                  </option>
                @endforeach
              </select>
              @error('chauffeur_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Date/heure</label>
              <input type="text" name="pese_le" class="form-control" placeholder="jj/mm/aaaa" value="{{ old('pese_le', optional($pesee->pese_le)->format('d/m/Y')) }}" />
              @error('pese_le')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Référence</label>
              <input type="text" name="reference" class="form-control" value="{{ old('reference', $pesee->reference) }}" />
              @error('reference')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Tare (calculée)</label>
              <input type="number" step="0.001" class="form-control" value="{{ $pesee->tare }}" readonly />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Après réfraction (calculé)</label>
              <input type="number" step="0.001" class="form-control" value="{{ $pesee->poids_apres_refraction }}" readonly />
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Poids brut</label>
              <input type="number" step="0.001" name="poids_brut" class="form-control" value="{{ old('poids_brut', $pesee->poids_brut) }}" required />
              @error('poids_brut')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Poids vide (optionnel)</label>
              <input type="number" step="0.001" name="poids_vide" class="form-control" value="{{ old('poids_vide', $pesee->poids_vide) }}" />
              @error('poids_vide')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label">Notes (optionnel)</label>
              <input type="text" name="notes" class="form-control" value="{{ old('notes', $pesee->notes) }}" />
              @error('notes')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
