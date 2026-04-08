<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntreeStock extends Model
{
    protected $table = 'entrees_stock';

    protected $fillable = [
        'stock_id',
        'quantite',
        'date_entree',
        'commentaire',
    ];

    protected $casts = [
        'date_entree' => 'date',
        'quantite' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
