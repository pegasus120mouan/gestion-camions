<?php

namespace App\Services;

use App\Models\EntreeStock;
use App\Models\EtapeTransfertGroupe;
use App\Models\FicheSortie;
use App\Models\Parc;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransfertGroupeService
{
    public function calculerSortiesStock(Stock $stock, ?int $excludeFicheId = null, ?int $excludeEtapeId = null): float
    {
        $parts = $this->calculerSortiesStockDetail($stock, $excludeFicheId, $excludeEtapeId);

        return $parts['normales'] + $parts['groupees'] + $parts['etapes'];
    }

    /**
     * @return array{normales: float, groupees: float, etapes: float}
     */
    public function calculerSortiesStockDetail(Stock $stock, ?int $excludeFicheId = null, ?int $excludeEtapeId = null): array
    {
        $sortiesNormales = FicheSortie::query()
            ->where('stock_id', $stock->id)
            ->whereNotNull('poids_pont')
            ->whereNotNull('date_dechargement')
            ->where(function ($normale) {
                $normale->whereNull('type_fiche')->orWhere('type_fiche', FicheSortie::TYPE_NORMALE);
            });

        $sortiesGroupees = FicheSortie::query()
            ->where('stock_id', $stock->id)
            ->whereNotNull('poids_pont')
            ->where('type_fiche', FicheSortie::TYPE_GROUPEE);

        if ($excludeFicheId) {
            $sortiesNormales->where('id', '!=', $excludeFicheId);
            $sortiesGroupees->where('id', '!=', $excludeFicheId);
        }

        $sortiesEtapes = EtapeTransfertGroupe::query()
            ->where('stock_source_id', $stock->id)
            ->where('type_etape', '!=', EtapeTransfertGroupe::TYPE_CHARGEMENT);

        if ($excludeEtapeId) {
            $sortiesEtapes->where('id', '!=', $excludeEtapeId);
        }

        return [
            'normales' => (float) $sortiesNormales->sum('poids_pont'),
            'groupees' => (float) $sortiesGroupees->sum('poids_pont'),
            'etapes' => (float) $sortiesEtapes->sum('poids'),
        ];
    }

    public function calculerStockDisponible(Stock $stock, ?int $excludeFicheId = null, ?int $excludeEtapeId = null): float
    {
        $entrees = (float) $stock->total_entrees;
        $sorties = $this->calculerSortiesStock($stock, $excludeFicheId, $excludeEtapeId);

        // Disponible = entrées − toutes les sorties (normales + groupées + étapes)
        return max(0, $entrees - $sorties);
    }

    /**
     * Stock « actuel » pour la chaîne de transferts groupés :
     * entrées − chargements groupés − reprises/clôtures (sans les sorties normales / écart).
     * Ex. : parc à 25 000 + transfert 25 000 → 50 000 à créditer au hop suivant.
     */
    public function calculerStockActuelChaine(Stock $stock, ?int $excludeFicheId = null, ?int $excludeEtapeId = null): float
    {
        $parts = $this->calculerSortiesStockDetail($stock, $excludeFicheId, $excludeEtapeId);
        $entrees = (float) $stock->total_entrees;

        return max(0, $entrees - $parts['groupees'] - $parts['etapes']);
    }

    /**
     * @return list<array{stock_id: int, parc_id: int|null, nom_parc: string, disponible: float, produit_id: int|null, nom_produit: string|null}>
     */
    public function stocksOuvertsPourPontProduit(int $idPont, int $produitId): array
    {
        $stocks = Stock::query()
            ->where('id_pont', $idPont)
            ->where('produit_id', $produitId)
            ->where('type', 'entree')
            ->where('statut', 'ouvert')
            ->where(function ($q) {
                $q->whereNull('etat')->orWhere('etat', 'actif');
            })
            ->whereHas('parc', fn ($p) => $p->where('id_pont', $idPont)->where('statut', 'actif'))
            ->orderBy('id')
            ->get();

        $result = [];
        foreach ($stocks as $stock) {
            $totalEntrees = (float) $stock->total_entrees;
            $result[] = [
                'stock_id' => (int) $stock->id,
                'parc_id' => $stock->parc_id ? (int) $stock->parc_id : null,
                'nom_parc' => (string) ($stock->nom_parc ?? 'Parc'),
                'total_entrees' => $totalEntrees,
                'disponible' => $this->calculerStockDisponible($stock),
                'produit_id' => $stock->produit_id ? (int) $stock->produit_id : null,
                'nom_produit' => $stock->nom_produit,
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $agent
     */
    public function enregistrerChargement(FicheSortie $fiche, array $agent, ?Stock $stockSource, float $poids): EtapeTransfertGroupe
    {
        if (! $fiche->estGroupee()) {
            throw new InvalidArgumentException('La fiche n\'est pas une fiche groupée.');
        }

        if ($stockSource) {
            $dispo = $this->calculerStockDisponible($stockSource, $fiche->id);
            if ($poids > $dispo + 0.0001) {
                throw new InvalidArgumentException(
                    'Stock insuffisant sur le parc (disponible : ' . number_format($dispo, 0, ',', ' ') . ' kg).'
                );
            }
        }

        return EtapeTransfertGroupe::create([
            'fiche_sortie_id' => $fiche->id,
            'ordre' => 1,
            'type_etape' => EtapeTransfertGroupe::TYPE_CHARGEMENT,
            'id_agent' => $agent['id_agent'] ?? $fiche->id_agent,
            'nom_agent' => $agent['nom_agent'] ?? $fiche->nom_agent,
            'numero_agent' => $agent['numero_agent'] ?? $fiche->numero_agent,
            'vehicule_id' => $fiche->vehicule_id,
            'matricule_vehicule' => $fiche->matricule_vehicule,
            'poids' => $poids,
            'parc_source_id' => $stockSource?->parc_id,
            'stock_source_id' => $stockSource?->id,
            'nom_parc_source' => $stockSource?->nom_parc,
            'date_etape' => $fiche->date_chargement?->toDateString() ?? now()->toDateString(),
            'commentaire' => 'Chargement initial — déduction parc source',
        ]);
    }

    /**
     * @param  array{id_agent: int, nom_agent?: string, numero_agent?: string, date_etape: string, commentaire?: string|null}  $data
     */
    public function transfererVersPont(FicheSortie $fiche, array $data, Stock $stockDestination): EtapeTransfertGroupe
    {
        if (! $fiche->groupeEnCours()) {
            throw new InvalidArgumentException('Cette fiche groupée n\'est plus en cours.');
        }

        if (! $stockDestination->accepteEntrees()) {
            throw new InvalidArgumentException('Le stock destination n\'accepte pas d\'entrées.');
        }

        if ((int) $stockDestination->produit_id !== (int) $fiche->produit_id) {
            throw new InvalidArgumentException('Le stock destination ne correspond pas au produit de la fiche.');
        }

        return DB::transaction(function () use ($fiche, $data, $stockDestination) {
            $stockSource = null;
            $parcSourceId = null;
            $nomParcSource = null;

            // Depuis un parc : on déplace le stock actuel de la chaîne
            // (stock parc + transferts déjà reçus), pas seulement le poids initial.
            // Depuis le camion (transit) : on déplace le poids de la fiche.
            if ($fiche->localisation_type === FicheSortie::LOCALISATION_PARC && $fiche->localisation_stock_id) {
                $stockSource = Stock::query()->find($fiche->localisation_stock_id);
                if (! $stockSource) {
                    throw new InvalidArgumentException('Stock de localisation introuvable.');
                }
                if ((int) $stockSource->id === (int) $stockDestination->id) {
                    throw new InvalidArgumentException('Le parc destination doit être différent du parc actuel.');
                }
                $poids = $this->calculerStockActuelChaine($stockSource);
                if ($poids <= 0) {
                    throw new InvalidArgumentException(
                        'Aucun stock actuel à transférer sur '
                        . ($stockSource->nom_parc ?? 'ce parc') . '.'
                    );
                }
                $parcSourceId = $stockSource->parc_id;
                $nomParcSource = $stockSource->nom_parc;
            } else {
                $poids = (float) ($fiche->poids_pont ?? 0);
                if ($poids <= 0) {
                    throw new InvalidArgumentException('Poids de la fiche invalide.');
                }
            }

            $entree = EntreeStock::create([
                'stock_id' => $stockDestination->id,
                'quantite' => $poids,
                'prix_unitaire' => $stockDestination->prix_unitaire,
                'montant_total' => $stockDestination->prix_unitaire
                    ? round((float) $stockDestination->prix_unitaire * $poids, 2)
                    : null,
                'date_entree' => $data['date_etape'],
                'commentaire' => sprintf(
                    'Transfert groupé fiche %s — agent %s (%s kg stock actuel source)',
                    $fiche->numero_fiche ?? $fiche->id,
                    $data['nom_agent'] ?? ($data['numero_agent'] ?? ''),
                    number_format($poids, 0, ',', ' ')
                ),
            ]);

            $ordre = (int) $fiche->etapesTransfertGroupe()->max('ordre') + 1;

            $etape = EtapeTransfertGroupe::create([
                'fiche_sortie_id' => $fiche->id,
                'ordre' => $ordre,
                'type_etape' => EtapeTransfertGroupe::TYPE_TRANSFERT_PONT,
                'id_agent' => $data['id_agent'],
                'nom_agent' => $data['nom_agent'] ?? null,
                'numero_agent' => $data['numero_agent'] ?? null,
                'vehicule_id' => $fiche->vehicule_id,
                'matricule_vehicule' => $fiche->matricule_vehicule,
                'poids' => $poids,
                'parc_source_id' => $parcSourceId,
                'stock_source_id' => $stockSource?->id,
                'nom_parc_source' => $nomParcSource,
                'id_pont_destination' => $stockDestination->id_pont,
                'nom_pont_destination' => $stockDestination->nom_pont,
                'code_pont_destination' => $stockDestination->code_pont,
                'parc_destination_id' => $stockDestination->parc_id,
                'stock_destination_id' => $stockDestination->id,
                'nom_parc_destination' => $stockDestination->nom_parc,
                'entree_stock_id' => $entree->id,
                'date_etape' => $data['date_etape'],
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            $fiche->update([
                'id_agent' => $data['id_agent'],
                'nom_agent' => $data['nom_agent'] ?? $fiche->nom_agent,
                'numero_agent' => $data['numero_agent'] ?? $fiche->numero_agent,
                'poids_pont' => $poids,
                'localisation_type' => FicheSortie::LOCALISATION_PARC,
                'localisation_parc_id' => $stockDestination->parc_id,
                'localisation_stock_id' => $stockDestination->id,
            ]);

            return $etape;
        });
    }

    /**
     * @param  array{id_agent: int, nom_agent?: string, numero_agent?: string, usine: string, date_etape: string, poids?: float, commentaire?: string|null}  $data
     */
    public function cloturerVersUsine(FicheSortie $fiche, array $data, Stock $stockSource): EtapeTransfertGroupe
    {
        if (! $fiche->groupeEnCours()) {
            throw new InvalidArgumentException('Cette fiche groupée n\'est plus en cours.');
        }

        $usine = trim((string) ($data['usine'] ?? ''));
        if ($usine === '') {
            throw new InvalidArgumentException('Usine de destination obligatoire.');
        }

        if ((int) $stockSource->produit_id !== (int) $fiche->produit_id) {
            throw new InvalidArgumentException('Le parc choisi ne correspond pas au produit de départ.');
        }

        if (! $stockSource->isOuvert() || ! $stockSource->isActif()) {
            throw new InvalidArgumentException('Le stock source n\'est pas ouvert / actif.');
        }

        $poids = (float) ($data['poids'] ?? 0);
        if ($poids <= 0) {
            throw new InvalidArgumentException('Poids invalide.');
        }

        // Si le parc choisi est celui du chargement initial, la déduction est déjà
        // comptée via fiche.stock_id — ne pas re-déduire via l'étape.
        $memeStockChargement = $fiche->stock_id && (int) $stockSource->id === (int) $fiche->stock_id;

        if (! $memeStockChargement) {
            $dispo = $this->calculerStockDisponible($stockSource);
            if ($poids > $dispo + 0.0001) {
                throw new InvalidArgumentException(
                    'Stock insuffisant sur le parc (disponible : ' . number_format($dispo, 0, ',', ' ') . ' kg).'
                );
            }
        }

        return DB::transaction(function () use ($fiche, $data, $usine, $poids, $stockSource, $memeStockChargement) {
            $ordre = (int) $fiche->etapesTransfertGroupe()->max('ordre') + 1;

            $etape = EtapeTransfertGroupe::create([
                'fiche_sortie_id' => $fiche->id,
                'ordre' => $ordre,
                'type_etape' => EtapeTransfertGroupe::TYPE_CLOTURE_USINE,
                'id_agent' => $data['id_agent'],
                'nom_agent' => $data['nom_agent'] ?? null,
                'numero_agent' => $data['numero_agent'] ?? null,
                'vehicule_id' => $fiche->vehicule_id,
                'matricule_vehicule' => $fiche->matricule_vehicule,
                'poids' => $poids,
                'parc_source_id' => $stockSource->parc_id,
                'stock_source_id' => $memeStockChargement ? null : $stockSource->id,
                'nom_parc_source' => $stockSource->nom_parc,
                'usine_destination' => $usine,
                'date_etape' => $data['date_etape'],
                'commentaire' => $data['commentaire'] ?? 'Clôture — destination usine',
            ]);

            // Clôture groupée = usine + fin des transferts, mais la fiche reste
            // « non déchargée » jusqu'au déchargement usine classique.
            $fiche->update([
                'id_agent' => $data['id_agent'],
                'nom_agent' => $data['nom_agent'] ?? $fiche->nom_agent,
                'numero_agent' => $data['numero_agent'] ?? $fiche->numero_agent,
                'usine' => $usine,
                'poids_pont' => $poids,
                'statut_groupe' => FicheSortie::STATUT_GROUPE_CLOTUREE,
                'localisation_type' => FicheSortie::LOCALISATION_TRANSIT,
                'localisation_parc_id' => null,
                'localisation_stock_id' => null,
            ]);

            return $etape;
        });
    }

    public function resoudreStockParc(int $parcId, int $produitId): ?Stock
    {
        $parc = Parc::query()->find($parcId);
        if (! $parc || $parc->statut !== 'actif') {
            return null;
        }

        return Stock::query()
            ->where('parc_id', $parcId)
            ->where('produit_id', $produitId)
            ->where('type', 'entree')
            ->where('statut', 'ouvert')
            ->where(function ($q) {
                $q->whereNull('etat')->orWhere('etat', 'actif');
            })
            ->orderBy('id')
            ->first();
    }
}
