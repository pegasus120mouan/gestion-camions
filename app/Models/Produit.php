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

    /**
     * Produit « Graine De Palme » (anciennement parfois nommé Palmier) :
     * les usines Unipalm (API) lui sont rattachées pour les selects.
     */
    public function isGraineDePalme(): bool
    {
        return self::nomEstGraineDePalme((string) $this->nom);
    }

    public static function nomEstGraineDePalme(?string $nom): bool
    {
        $n = mb_strtolower(trim((string) $nom), 'UTF-8');
        if ($n === '') {
            return false;
        }

        // Accents / orthographes usuelles
        $n = strtr($n, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a',
        ]);

        return $n === 'palmier'
            || $n === 'palme'
            || (str_contains($n, 'graine') && str_contains($n, 'palme'))
            || str_contains($n, 'graine de palme');
    }

    public static function graineDePalme(): ?self
    {
        return static::query()
            ->orderBy('nom')
            ->get()
            ->first(fn (self $produit) => $produit->isGraineDePalme());
    }
}
