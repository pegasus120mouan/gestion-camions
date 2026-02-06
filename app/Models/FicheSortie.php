<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FicheSortie extends Model
{
    protected $table = 'fiches_sortie';

    protected $fillable = [
        'vehicule_id',
        'matricule_vehicule',
        'id_pont',
        'nom_pont',
        'code_pont',
        'id_agent',
        'nom_agent',
        'numero_agent',
        'date_chargement',
        'poids_pont',
        'id_ticket',
        'numero_ticket',
        'prix_unitaire_transport',
        'poids_unitaire_regime',
    ];

    protected $casts = [
        'date_chargement' => 'date',
        'poids_pont' => 'decimal:2',
    ];
}
