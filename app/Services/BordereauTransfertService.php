<?php

namespace App\Services;

use App\Models\BordereauTransfert;
use App\Models\Transfert;
use Illuminate\Support\Collection;

class BordereauTransfertService
{
    public function genererNumero(string $clientType, string $clientId): string
    {
        $prefix = $clientType === 'usine' ? 'BORD-TU' : 'BORD-TP';
        $count = BordereauTransfert::query()
            ->where('client_type', $clientType)
            ->where('client_id', $clientId)
            ->count() + 1;

        return $prefix . '-' . $clientId . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @return Collection<int, Transfert>
     */
    public function lignesEligibles(string $clientType, string $clientId, string $dateDebut, string $dateFin): Collection
    {
        return Transfert::query()
            ->where('client_type', $clientType)
            ->where('client_id', $clientId)
            ->whereNull('bordereau_transfert_id')
            ->where('statut', Transfert::STATUT_DECHARGE)
            ->whereNotNull('montant')
            ->where('montant', '>', 0)
            ->whereDate('date_chargement', '>=', $dateDebut)
            ->whereDate('date_chargement', '<=', $dateFin)
            ->orderBy('date_chargement')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Transfert>|iterable<Transfert>  $transferts
     * @return list<array<string, mixed>>
     */
    public function construireLignesData(iterable $transferts): array
    {
        $lignes = [];
        foreach ($transferts as $transfert) {
            $poids = $transfert->poids_arrivee ?? $transfert->poids_depart;
            $lignes[] = [
                'transfert_id' => $transfert->id,
                'date_chargement' => $transfert->date_chargement?->format('Y-m-d'),
                'matricule_vehicule' => $transfert->matricule_vehicule,
                'lieu_depart' => $transfert->lieu_depart,
                'lieu_destination' => $transfert->lieu_destination,
                'poids' => $poids !== null ? (float) $poids : 0,
                'prix_unitaire' => $transfert->prix_unitaire !== null ? (float) $transfert->prix_unitaire : null,
                'montant' => (float) $transfert->montant,
            ];
        }

        return $lignes;
    }

    /**
     * @param  list<array<string, mixed>>  $lignesData
     */
    public function assignerLignesAuBordereau(BordereauTransfert $bordereau, array $lignesData): void
    {
        $ids = collect($lignesData)->pluck('transfert_id')->filter()->unique()->values()->all();
        if ($ids === []) {
            return;
        }

        Transfert::query()
            ->whereIn('id', $ids)
            ->whereNull('bordereau_transfert_id')
            ->update(['bordereau_transfert_id' => $bordereau->id]);
    }

    public function libererLignes(BordereauTransfert $bordereau): void
    {
        Transfert::query()
            ->where('bordereau_transfert_id', $bordereau->id)
            ->update(['bordereau_transfert_id' => null]);
    }
}
