<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrixAgent extends Model
{
    protected $table = 'prix_agents';

    protected $fillable = [
        'id_agent',
        'id_usine',
        'nom_usine',
        'type',
        'prix',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];
}
