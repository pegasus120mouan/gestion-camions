<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransfertPont extends Model
{
    protected $table = 'transfert_ponts';

    protected $fillable = [
        'date_transfert',
        'vehicule_id',
        'matricule_vehicule',
        'produit_id',
        'nom_produit',
        'id_pont_source',
        'nom_pont_source',
        'code_pont_source',
        'parc_source_id',
        'stock_source_id',
        'nom_parc_source',
        'poids_depart',
        'id_pont_destination',
        'nom_pont_destination',
        'code_pont_destination',
        'parc_destination_id',
        'stock_destination_id',
        'nom_parc_destination',
        'poids_arrivee',
        'entree_stock_id',
        'commentaire',
        'created_by',
    ];

    protected $casts = [
        'date_transfert' => 'date',
        'poids_depart' => 'decimal:2',
        'poids_arrivee' => 'decimal:2',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function stockSource(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_source_id');
    }

    public function stockDestination(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_destination_id');
    }
}
