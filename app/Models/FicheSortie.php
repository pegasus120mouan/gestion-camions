<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FicheSortie extends Model
{
    public const TYPE_NORMALE = 'normale';
    public const TYPE_GROUPEE = 'groupee';

    public const STATUT_GROUPE_EN_COURS = 'en_cours';
    public const STATUT_GROUPE_CLOTUREE = 'cloturee';

    public const LOCALISATION_TRANSIT = 'transit';
    public const LOCALISATION_PARC = 'parc';

    protected $table = 'fiches_sortie';

    protected $fillable = [
        'numero_fiche',
        'type_fiche',
        'statut_groupe',
        'localisation_type',
        'localisation_parc_id',
        'localisation_stock_id',
        'stock_id',
        'parc_id',
        'nom_parc',
        'vehicule_id',
        'matricule_vehicule',
        'transporteur_id',
        'id_pont',
        'nom_pont',
        'code_pont',
        'usine',
        'produit_id',
        'nom_produit',
        'id_agent',
        'bordereau_agent_id',
        'bordereau_transporteur_id',
        'nom_agent',
        'numero_agent',
        'id_chef_chargeur',
        'date_chargement',
        'date_dechargement',
        'poids_pont',
        'prix_unitaire_camion',
        'montant_camion',
        'carburant',
        'frais_route',
        'paiement_chargeur',
        'id_ticket',
        'numero_ticket',
        'prix_unitaire_transport',
        'poids_unitaire_regime',
        'montant_paye_transporteur',
        'montant_agent',
    ];

    protected $casts = [
        'date_chargement' => 'date',
        'date_dechargement' => 'date',
        'poids_pont' => 'decimal:2',
        'prix_unitaire_camion' => 'decimal:2',
        'montant_camion' => 'decimal:2',
        'montant_agent' => 'decimal:2',
        'transporteur_id' => 'integer',
    ];

    public function transporteur()
    {
        return $this->belongsTo(Transporteur::class);
    }

    public function etapesTransfertGroupe(): HasMany
    {
        return $this->hasMany(EtapeTransfertGroupe::class, 'fiche_sortie_id')->orderBy('ordre');
    }

    public function estGroupee(): bool
    {
        return ($this->type_fiche ?? self::TYPE_NORMALE) === self::TYPE_GROUPEE;
    }

    public function groupeEnCours(): bool
    {
        return $this->estGroupee() && ($this->statut_groupe === self::STATUT_GROUPE_EN_COURS);
    }
}
