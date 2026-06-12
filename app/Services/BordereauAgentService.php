<?php

namespace App\Services;

use App\Models\BordereauAgent;
use App\Models\FicheSortie;
use Illuminate\Support\Facades\DB;

class BordereauAgentService
{
    public function __construct(
        private MontantAgentReportingService $reporting,
        private MontantAgentFicheService $montantAgentFiche
    ) {}

    public function lettresAgent(?string $numeroAgent, ?string $nomAgent = null): string
    {
        if ($numeroAgent) {
            $segments = explode('-', strtoupper(trim($numeroAgent)));
            $suffixe = (string) end($segments);
            $letters = preg_replace('/[^A-Z]/u', '', $suffixe) ?? '';

            if (mb_strlen($letters) >= 2) {
                return mb_substr($letters, 0, 2);
            }

            if ($letters !== '') {
                return str_pad($letters, 2, 'X');
            }
        }

        $letters = preg_replace('/[^A-Z]/u', '', mb_strtoupper(trim((string) $nomAgent), 'UTF-8')) ?? '';

        if (mb_strlen($letters) >= 2) {
            return mb_substr($letters, 0, 2);
        }

        if ($letters !== '') {
            return str_pad($letters, 2, 'X');
        }

        return 'XX';
    }

    public function genererNumero(?string $numeroAgent = null, ?string $nomAgent = null): string
    {
        $prefix = 'BORD-' . $this->lettresAgent($numeroAgent, $nomAgent);

        return DB::transaction(function () use ($prefix) {
            $numeros = BordereauAgent::query()
                ->where('numero', 'like', $prefix . '%')
                ->lockForUpdate()
                ->pluck('numero');

            $max = 0;
            foreach ($numeros as $numero) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $numero, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            }

            return $prefix . ($max + 1);
        });
    }

    public function exempleNumero(?string $numeroAgent, ?string $nomAgent = null): string
    {
        return 'BORD-' . $this->lettresAgent($numeroAgent, $nomAgent) . '1';
    }

    /**
     * @return list<int>
     */
    public function ficheIdsDejaBorderees(int $idAgent): array
    {
        return FicheSortie::query()
            ->where('id_agent', $idAgent)
            ->whereNotNull('bordereau_agent_id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function assignerFichesAuBordereau(BordereauAgent $bordereau, array $ficheIds): void
    {
        $ficheIds = array_values(array_unique(array_map('intval', $ficheIds)));
        if ($ficheIds === []) {
            return;
        }

        FicheSortie::query()
            ->where('id_agent', $bordereau->id_agent)
            ->whereIn('id', $ficheIds)
            ->whereNull('bordereau_agent_id')
            ->update(['bordereau_agent_id' => $bordereau->id]);
    }

    public function libererFichesDuBordereau(BordereauAgent $bordereau): void
    {
        FicheSortie::query()
            ->where('bordereau_agent_id', $bordereau->id)
            ->update(['bordereau_agent_id' => null]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fichesEligibles(int $idAgent, string $dateDebut, string $dateFin): array
    {
        $fichesAvecMontant = $this->reporting->fichesAvecMontant([
            'id_agent' => $idAgent,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'sans_bordereau' => true,
        ]);

        $lignes = [];
        foreach ($fichesAvecMontant as $item) {
            $lignes[] = $this->serialiserLigneFiche($item);
        }

        return $lignes;
    }

    /**
     * @param  list<int>  $ficheIds
     * @return list<array<string, mixed>>
     */
    public function construireFichesData(int $idAgent, array $ficheIds): array
    {
        $ficheIds = array_values(array_unique(array_map('intval', $ficheIds)));
        if ($ficheIds === []) {
            return [];
        }

        $fiches = FicheSortie::query()
            ->where('id_agent', $idAgent)
            ->whereIn('id', $ficheIds)
            ->whereNotNull('date_dechargement')
            ->whereNull('bordereau_agent_id')
            ->get()
            ->keyBy('id');

        $lignes = [];
        foreach ($ficheIds as $ficheId) {
            $fiche = $fiches->get($ficheId);
            if (!$fiche) {
                continue;
            }

            $montant = $this->reporting->montantLigneFiche($fiche);
            $poids = (float) $fiche->poids_pont;
            if ($poids <= 0 && $fiche->id_ticket) {
                $ticket = \App\Models\Ticket::where('id_ticket', $fiche->id_ticket)->first();
                $poids = $ticket ? (float) ($ticket->poids ?? 0) : 0;
            }

            $lignes[] = $this->serialiserLigneFiche([
                'fiche' => $fiche,
                'montant' => $montant,
                'prix_unitaire' => $this->montantAgentFiche->prixUnitairePourFiche($fiche),
                'poids_effectif' => $poids,
            ]);
        }

        return $lignes;
    }

    /**
     * @param  array{fiche: FicheSortie, montant: int, prix_unitaire: float|null, poids_effectif?: float}  $item
     * @return array<string, mixed>
     */
    private function serialiserLigneFiche(array $item): array
    {
        $fiche = $item['fiche'];
        $poids = (float) ($item['poids_effectif'] ?? $fiche->poids_pont ?? 0);

        return [
            'fiche_id' => (int) $fiche->id,
            'numero_fiche' => $fiche->numero_fiche,
            'date_chargement' => $fiche->date_chargement?->format('Y-m-d'),
            'date_dechargement' => $fiche->date_dechargement?->format('Y-m-d'),
            'matricule_vehicule' => $fiche->matricule_vehicule,
            'nom_produit' => $fiche->nom_produit,
            'usine' => $fiche->usine,
            'numero_ticket' => $fiche->numero_ticket,
            'poids' => $poids,
            'prix_unitaire' => $item['prix_unitaire'] ?? null,
            'montant' => (int) ($item['montant'] ?? 0),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fichesData
     * @return list<array{usine: string, lignes: list<array<string, mixed>>, montant_total: int, poids_total: float}>
     */
    public function grouperParUsine(array $fichesData): array
    {
        $parUsine = collect($fichesData)->groupBy(fn ($l) => $l['usine'] ?: 'Sans usine');
        $groupes = [];

        foreach ($parUsine as $usine => $lignes) {
            $lignesArr = $lignes->values()->all();
            $groupes[] = [
                'usine' => $usine,
                'lignes' => $lignesArr,
                'montant_total' => (int) collect($lignesArr)->sum('montant'),
                'poids_total' => (float) collect($lignesArr)->sum('poids'),
            ];
        }

        usort($groupes, fn ($a, $b) => strcasecmp($a['usine'], $b['usine']));

        return $groupes;
    }
}
