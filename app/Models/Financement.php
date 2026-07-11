<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Financement extends Model
{
    protected $table = 'financement';

    protected $primaryKey = 'Numero_financement';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'Numero_financement',
        'code_financement',
        'id_agent',
        'montant',
        'motif',
        'date_financement',
    ];

    protected $casts = [
        'date_financement' => 'datetime',
        'montant' => 'decimal:2',
    ];

    public function getCodeAfficheAttribute(): string
    {
        return (string) ($this->code_financement ?: $this->Numero_financement);
    }

    public function isAdvance(): bool
    {
        return (float) $this->montant > 0;
    }

    public function isRepayment(): bool
    {
        return (float) $this->montant < 0;
    }
}
