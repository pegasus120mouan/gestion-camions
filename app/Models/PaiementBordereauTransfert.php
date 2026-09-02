<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaiementBordereauTransfert extends Model
{
    protected $table = 'paiements_bordereau_transfert';

    protected $fillable = [
        'bordereau_transfert_id',
        'client_type',
        'client_id',
        'montant',
        'date_paiement',
        'observation',
        'user_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    public function bordereau(): BelongsTo
    {
        return $this->belongsTo(BordereauTransfert::class, 'bordereau_transfert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
