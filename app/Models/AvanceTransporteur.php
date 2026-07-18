<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvanceTransporteur extends Model
{
    protected $table = 'avances_transporteur';

    protected $fillable = [
        'transporteur_id',
        'montant',
        'date_avance',
        'mode_paiement',
        'reference',
        'commentaire',
    ];

    protected $casts = [
        'montant' => 'integer',
        'date_avance' => 'date',
    ];

    public function transporteur(): BelongsTo
    {
        return $this->belongsTo(Transporteur::class);
    }
}
