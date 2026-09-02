<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfert extends Model
{
    protected $table = 'transferts';

    protected $fillable = [
        'date_chargement',
        'vehicule_id',
        'matricule_vehicule',
        'client',
        'client_type',
        'client_id',
        'lieu_depart',
        'lieu_destination',
        'poids_depart',
        'poids_arrivee',
        'prix_unitaire',
        'montant',
        'statut',
        'paiement',
        'bordereau_transfert_id',
        'commentaire',
        'created_by',
    ];

    public const STATUT_NON_DECHARGE = 'non_decharge';
    public const STATUT_DECHARGE = 'decharge';

    public const PAIEMENT_NON_PAYE = 'non_paye';
    public const PAIEMENT_PAYE = 'paye';

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_DECHARGE => 'Déchargé',
            default => 'Non déchargé',
        };
    }

    public function getPaiementLabelAttribute(): string
    {
        return match ($this->paiement) {
            self::PAIEMENT_PAYE => 'Payé',
            default => 'Non payé',
        };
    }

    protected $casts = [
        'date_chargement' => 'date',
        'poids_depart' => 'decimal:2',
        'poids_arrivee' => 'decimal:2',
        'prix_unitaire' => 'decimal:2',
        'montant' => 'decimal:2',
    ];
}
