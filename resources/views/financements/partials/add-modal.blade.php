<div class="modal fade" id="addFinancementModal" tabindex="-1" aria-labelledby="addFinancementModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addFinancementModalLabel">Nouveau financement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <form method="POST" action="{{ route('financements.store') }}">
        @csrf
        @if (! empty($redirectTo))
          <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
        @endif
        <div class="modal-body">
          <div class="mb-3">
            <label for="financement_agent_id" class="form-label">Agent</label>
            @if (! empty($selectedAgentId))
              @php
                $selectedAgent = collect($agents)->firstWhere('id_agent', (int) $selectedAgentId);
              @endphp
              <input type="hidden" name="id_agent" value="{{ $selectedAgentId }}">
              <input type="text" id="financement_agent_id" class="form-control"
                value="{{ $selectedAgent['nom_complet'] ?? ('Agent #'.$selectedAgentId) }}" disabled>
            @else
              <select name="id_agent" id="financement_agent_id"
                class="form-select @error('id_agent') is-invalid @enderror" required>
                <option value="">Sélectionner un agent</option>
                @foreach ($agents as $agentOption)
                  <option value="{{ $agentOption['id_agent'] }}"
                    @selected(old('id_agent') == $agentOption['id_agent'])>
                    {{ $agentOption['nom_complet'] }}
                  </option>
                @endforeach
              </select>
            @endif
            @error('id_agent')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="financement_montant" class="form-label">Montant (FCFA)</label>
            <input type="text" name="montant" id="financement_montant"
              class="form-control @error('montant') is-invalid @enderror"
              inputmode="numeric" autocomplete="off" placeholder="Ex : 2 000 000"
              value="{{ old('montant') }}" required>
            @error('montant')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="financement_motif" class="form-label">Motif</label>
            <textarea name="motif" id="financement_motif" rows="3"
              class="form-control @error('motif') is-invalid @enderror"
              required>{{ old('motif') }}</textarea>
            @error('motif')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i>Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
