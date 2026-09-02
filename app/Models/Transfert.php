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
        'montant',
        'statut',
        'commentaire',
        'created_by',
    ];

    public const STATUT_NON_DECHARGE = 'non_decharge';
    public const STATUT_DECHARGE = 'decharge';

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_DECHARGE => 'Déchargé',
            default => 'Non déchargé',
        };
    }

    protected $casts = [
        'date_chargement' => 'date',
        'poids_depart' => 'decimal:2',
        'poids_arrivee' => 'decimal:2',
        'montant' => 'decimal:2',
    ];
}
