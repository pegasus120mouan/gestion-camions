<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementAgent extends Model
{
    protected $table = 'paiements_agent';

    protected $fillable = [
        'id_agent',
        'id_bordereau',
        'montant',
        'date_paiement',
        'mode_paiement',
        'reference',
        'commentaire',
    ];

    protected $casts = [
        'date_paiement' => 'date',
    ];

    public function bordereau()
    {
        return $this->belongsTo(BordereauAgent::class, 'id_bordereau');
    }
}
