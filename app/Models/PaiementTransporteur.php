<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementTransporteur extends Model
{
    protected $table = 'paiements_transporteur';

    protected $fillable = [
        'fiche_sortie_id',
        'matricule_vehicule',
        'montant',
        'date_paiement',
        'observation',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    public function ficheSortie()
    {
        return $this->belongsTo(FicheSortie::class, 'fiche_sortie_id');
    }
}
