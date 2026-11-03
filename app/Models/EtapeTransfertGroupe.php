<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeTransfertGroupe extends Model
{
    protected $table = 'etapes_transfert_groupe';

    public const TYPE_CHARGEMENT = 'chargement';
    public const TYPE_TRANSFERT_PONT = 'transfert_pont';
    public const TYPE_CLOTURE_USINE = 'cloture_usine';

    protected $fillable = [
        'fiche_sortie_id',
        'ordre',
        'type_etape',
        'id_agent',
        'nom_agent',
        'numero_agent',
        'vehicule_id',
        'matricule_vehicule',
        'poids',
        'parc_source_id',
        'stock_source_id',
        'nom_parc_source',
        'id_pont_destination',
        'nom_pont_destination',
        'code_pont_destination',
        'parc_destination_id',
        'stock_destination_id',
        'nom_parc_destination',
        'entree_stock_id',
        'usine_destination',
        'date_etape',
        'commentaire',
    ];

    protected $casts = [
        'date_etape' => 'date',
        'poids' => 'decimal:2',
        'ordre' => 'integer',
    ];

    public function ficheSortie(): BelongsTo
    {
        return $this->belongsTo(FicheSortie::class, 'fiche_sortie_id');
    }

    public function libelleType(): string
    {
        return match ($this->type_etape) {
            self::TYPE_CHARGEMENT => 'Chargement',
            self::TYPE_TRANSFERT_PONT => 'Transfert vers pont',
            self::TYPE_CLOTURE_USINE => 'Clôture usine',
            default => $this->type_etape,
        };
    }
}
