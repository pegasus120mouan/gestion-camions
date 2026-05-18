<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FicheSortie extends Model
{
    protected $table = 'fiches_sortie';

    protected $fillable = [
        'stock_id',
        'parc_id',
        'nom_parc',
        'vehicule_id',
        'matricule_vehicule',
        'id_pont',
        'nom_pont',
        'code_pont',
        'usine',
        'produit_id',
        'nom_produit',
        'id_agent',
        'nom_agent',
        'numero_agent',
        'id_chef_chargeur',
        'date_chargement',
        'date_dechargement',
        'poids_pont',
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
        'montant_agent' => 'decimal:2',
    ];
}
