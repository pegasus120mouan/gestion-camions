<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'code',
        'nom',
        'prenoms',
        'contact',
        'adresse',
    ];

    public function getNomCompletAttribute(): string
    {
        return trim($this->nom . ' ' . ($this->prenoms ?? ''));
    }

    public static function prochainCode(): string
    {
        $lastCode = static::query()
            ->where('code', 'like', 'CLI-%')
            ->orderByDesc('id')
            ->value('code');

        if ($lastCode && preg_match('/^CLI-(\d+)$/', $lastCode, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = static::count() + 1;
        }

        return 'CLI-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
