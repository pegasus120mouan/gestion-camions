<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transporteur extends Model
{
    protected $fillable = [
        'code',
        'nom',
        'prenoms',
    ];

    public function vehicules(): HasMany
    {
        return $this->hasMany(TransporteurVehicule::class);
    }

    public function paiementsGestion(): HasMany
    {
        return $this->hasMany(PaiementTransporteurGestion::class)->orderBy('date_paiement', 'desc');
    }
}
