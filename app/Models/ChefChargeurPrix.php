<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChefChargeurPrix extends Model
{
    protected $table = 'chef_chargeur_prix';

    protected $fillable = [
        'id_chef_chargeur',
        'produit_id',
        'nom_produit',
        'prix_unitaire',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function chefChargeur(): BelongsTo
    {
        return $this->belongsTo(ChefChargeur::class, 'id_chef_chargeur');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public static function findApplicable(int $chefChargeurId, $date, ?int $produitId = null): ?self
    {
        $base = static::query()
            ->where('id_chef_chargeur', $chefChargeurId)
            ->whereDate('date_debut', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>=', $date);
            });

        if ($produitId) {
            $match = (clone $base)->where('produit_id', $produitId)->orderByDesc('date_debut')->first();
            if ($match) {
                return $match;
            }
        }

        return (clone $base)
            ->whereNull('produit_id')
            ->orderByDesc('date_debut')
            ->first();
    }
}
