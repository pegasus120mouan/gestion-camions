<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Usine extends Model
{
    protected $table = 'usines';
    protected $primaryKey = 'id_usine';

    /** Les usines locales utilisent un espace d'ids distinct de l'API Unipalm. */
    public const LOCAL_ID_MIN = 5000;

    protected $fillable = [
        'nom_usine',
        'code_usine',
        'produit_id',
        'gerable',
    ];

    protected $casts = [
        'gerable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Usine $usine) {
            if (! empty($usine->id_usine) && (int) $usine->id_usine >= self::LOCAL_ID_MIN) {
                return;
            }

            $max = (int) (DB::table('usines')->max('id_usine') ?? 0);
            $usine->id_usine = max(self::LOCAL_ID_MIN, $max + 1);
        });
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public static function isLocalId(?int $idUsine): bool
    {
        return $idUsine !== null && $idUsine >= self::LOCAL_ID_MIN;
    }
}
