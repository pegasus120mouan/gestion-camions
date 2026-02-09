<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeTransporteur extends Model
{
    protected $table = 'code_transporteurs';

    protected $fillable = [
        'nom',
    ];

    public function vehicules()
    {
        return $this->hasMany(CodeTransporteurVehicule::class, 'code_transporteur_id');
    }
}
