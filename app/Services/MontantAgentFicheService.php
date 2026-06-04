<?php

namespace App\Services;

use App\Models\CodeTransporteurVehicule;
use App\Models\FicheSortie;
use App\Models\PrixAgent;

class MontantAgentFicheService
{
    /**
     * Type de grille tarifaire PrixAgent : transporteur (camion Pisteur), pgf, autre_camion.
     */
    public function typePrixPourMatricule(?string $matricule): string
    {
        if ($matricule === null || $matricule === '') {
            return 'transporteur';
        }

        $link = CodeTransporteurVehicule::with('codeTransporteur')
            ->where('matricule_vehicule', $matricule)
            ->first();

        if (!$link || !$link->codeTransporteur) {
            return 'transporteur';
        }

        $nom = trim((string) $link->codeTransporteur->nom);
        if ($nom === 'Camion PGF') {
            return 'pgf';
        }
        if (strcasecmp($nom, 'Autre Camion') === 0 || strcasecmp($nom, 'Autre') === 0) {
            return 'autre_camion';
        }

        return 'transporteur';
    }

    public function prixUnitairePourFiche(FicheSortie $fiche): ?float
    {
        if (!$fiche->id_agent || !$fiche->usine) {
            return null;
        }

        $usine = trim((string) $fiche->usine);
        if ($usine === '') {
            return null;
        }

        $type = $this->typePrixPourMatricule($fiche->matricule_vehicule);

        $query = PrixAgent::query()
            ->where('id_agent', $fiche->id_agent)
            ->where('nom_usine', $usine)
            ->where('type', $type);

        $dateRef = $fiche->date_chargement?->format('Y-m-d');
        if ($dateRef) {
            $query->where(function ($q) use ($dateRef) {
                $q->where(function ($q2) use ($dateRef) {
                    $q2->whereNull('date_debut')
                        ->orWhere('date_debut', '<=', $dateRef);
                })->where(function ($q3) use ($dateRef) {
                    $q3->whereNull('date_fin')
                        ->orWhere('date_fin', '>=', $dateRef);
                });
            });
        }

        $row = null;
        if ($fiche->produit_id) {
            $row = (clone $query)->where('produit_id', $fiche->produit_id)->first();
        }
        if (!$row) {
            $row = (clone $query)->whereNull('produit_id')->first();
        }
        if (!$row) {
            $row = $query->first();
        }

        return $row ? (float) $row->prix : null;
    }

    /**
     * Montant total dû à l’agent pour cette fiche (FCFA) : PU (FCFA/kg) × poids (kg).
     *
     * @param  float|null  $poidsKg  Si fourni (ex. poids saisi au déchargement), remplace celui de la fiche.
     */
    public function calculerMontantPourFiche(FicheSortie $fiche, ?float $poidsKg = null): ?float
    {
        $pu = $this->prixUnitairePourFiche($fiche);
        if ($pu === null) {
            return null;
        }

        $poids = $poidsKg ?? (float) $fiche->poids_pont;
        if ($poids <= 0) {
            return null;
        }

        return $pu * $poids;
    }
}
