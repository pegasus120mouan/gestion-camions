<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'id_pont',
        'code_pont',
        'nom_pont',
        'type',
        'quantite',
        'date_mouvement',
    ];

    protected $casts = [
        'date_mouvement' => 'date',
        'quantite' => 'decimal:2',
    ];
}
