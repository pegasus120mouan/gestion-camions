<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'id_ticket';

    protected $fillable = [
        'id_usine',
        'date_ticket',
        'id_agent',
        'numero_ticket',
        'vehicule_id',
        'matricule_vehicule',
        'poids',
        'id_utilisateur',
        'prix_unitaire',
        'date_validation_boss',
        'montant_paie',
        'montant_payer',
        'montant_reste',
        'date_paie',
        'statut_ticket',
        'numero_bordereau',
        'conformite',
        'poids_unipalm',
        'date_confirmation_unipalm',
    ];

    protected $casts = [
        'date_ticket' => 'date',
        'date_validation_boss' => 'datetime',
        'date_paie' => 'datetime',
        'poids' => 'float',
        'prix_unitaire' => 'decimal:2',
        'montant_paie' => 'decimal:2',
        'montant_payer' => 'decimal:2',
        'montant_reste' => 'decimal:2',
    ];

    public function ficheSortie()
    {
        return $this->hasOne(FicheSortie::class, 'id_ticket', 'id_ticket');
    }
}
