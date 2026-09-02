<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSite extends Model
{
    protected $table = 'client_sites';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'owner_nom',
        'nom',
        'adresse',
        'contact',
        'commentaire',
    ];

    public function scopeForOwner($query, string $type, string|int $id)
    {
        return $query->where('owner_type', $type)->where('owner_id', (string) $id);
    }
}
