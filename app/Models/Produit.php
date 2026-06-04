<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $table = 'produits';

    protected $fillable = [
        'nom',
        'tare',
    ];

    protected $casts = [
        'tare' => 'decimal:3',
    ];

    public function usines()
    {
        return $this->hasMany(Usine::class);
    }
}
