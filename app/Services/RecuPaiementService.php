<?php

namespace App\Services;

use App\Models\PaiementAgent;
use Illuminate\Support\Facades\Http;

class RecuPaiementService
{
    public function genererNumero(PaiementAgent $paiement): string
    {
        $date = $paiement->date_paiement ?? now();

        return $date->format('Ymd') . str_pad((string) $paiement->id, 4, '0', STR_PAD_LEFT);
    }

    public function assignerNumero(PaiementAgent $paiement): PaiementAgent
    {
        if ($paiement->numero_recu) {
            return $paiement;
        }

        $paiement->update(['numero_recu' => $this->genererNumero($paiement)]);

        return $paiement->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function agentApiParId(int $idAgent): ?array
    {
        $mesAgentsUrl = (string) config('services.external_auth.mes_agents_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $page = 1;

        try {
            while ($page <= 50) {
                $response = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get($mesAgentsUrl, ['page' => $page]);

                if (!$response->successful()) {
                    break;
                }

                foreach ($response->json('agents') ?? [] as $agent) {
                    if ((int) ($agent['id_agent'] ?? 0) === $idAgent) {
                        return $agent;
                    }
                }

                $pagination = $response->json('pagination') ?? [];
                if ($page >= (int) ($pagination['last_page'] ?? 1)) {
                    break;
                }
                $page++;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function donneesPdf(PaiementAgent $paiement, ?string $nomCaissier = null): array
    {
        $paiement->loadMissing('bordereau');
        $bordereau = $paiement->bordereau;
        $agentApi = $this->agentApiParId((int) $paiement->id_agent);

        $nomAgent = trim((string) ($bordereau?->agent_nom ?? ''));
        if ($nomAgent === '' && $agentApi) {
            $nomAgent = trim((string) ($agentApi['nom_complet'] ?? ''));
        }
        if ($nomAgent === '') {
            $nomAgent = 'Agent #' . $paiement->id_agent;
        }

        $contact = trim((string) ($agentApi['contact'] ?? ''));
        if ($contact === '') {
            $contact = '—';
        }

        $mode = trim((string) ($paiement->mode_paiement ?? ''));
        $reference = trim((string) ($paiement->reference ?? ''));
        $source = $mode !== '' ? $mode : '—';
        if ($reference !== '') {
            $source .= ($mode !== '' ? ' N° ' : 'N° ') . $reference;
        }

        $montantTotal = (int) round((float) ($bordereau?->montant_total ?? 0));
        $montantPayeLigne = (int) $paiement->montant;
        $reste = max(0, (int) round((float) ($bordereau?->reste_a_payer ?? 0)));

        $dateHeure = ($paiement->created_at ?? $paiement->date_paiement ?? now())->format('d/m/Y H:i');
        $dateFait = ($paiement->date_paiement ?? now())->format('d/m/Y');

        $logoPath = null;
        foreach (['img/logo/unipalm.png', 'img/logo/unipalm.jpg', 'img/logo/logo-unipalm.png'] as $rel) {
            $candidate = public_path($rel);
            if (file_exists($candidate)) {
                $logoPath = $candidate;
                break;
            }
        }

        return [
            'numeroRecu' => $paiement->numero_recu ?? $this->genererNumero($paiement),
            'numeroBordereau' => $bordereau?->numero ?? '—',
            'dateHeure' => $dateHeure,
            'dateFait' => $dateFait,
            'nomAgent' => $nomAgent,
            'contactAgent' => $contact,
            'montantTotal' => $montantTotal,
            'montantPaye' => $montantPayeLigne,
            'sourcePaiement' => $source,
            'resteAPayer' => $reste,
            'nomCaissier' => $nomCaissier ?: 'Caissier',
            'nomRecepteur' => $nomAgent,
            'logoPath' => $logoPath,
        ];
    }
}
