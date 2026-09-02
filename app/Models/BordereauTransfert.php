<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BordereauTransfert extends Model
{
    protected $table = 'bordereaux_transfert';

    protected $fillable = [
        'client_type',
        'client_id',
        'client_nom',
        'client_code',
        'numero',
        'date_generation',
        'date_debut',
        'date_fin',
        'montant_total',
        'montant_paye',
        'poids_total',
        'transferts_data',
    ];

    protected $casts = [
        'date_generation' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'poids_total' => 'decimal:2',
        'transferts_data' => 'array',
    ];

    public function getResteAPayerAttribute(): float
    {
        return max(0, (float) $this->montant_total - (float) ($this->montant_paye ?? 0));
    }

    public function transferts()
    {
        return $this->hasMany(Transfert::class, 'bordereau_transfert_id');
    }

    public function paiements()
    {
        return $this->hasMany(PaiementBordereauTransfert::class, 'bordereau_transfert_id');
    }
}
