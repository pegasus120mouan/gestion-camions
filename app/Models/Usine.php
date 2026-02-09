<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usine extends Model
{
    protected $table = 'usines';
    protected $primaryKey = 'id_usine';

    protected $fillable = [
        'nom_usine',
        'code_usine',
    ];
}
