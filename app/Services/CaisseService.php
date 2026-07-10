<?php

namespace App\Services;

use App\Models\CaisseMouvement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CaisseService
{
    public function getSolde(): float
    {
        $row = CaisseMouvement::query()
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type = 'approvisionnement' THEN montant
                WHEN type = 'paiement' THEN -montant
                ELSE 0
            END), 0) AS solde")
            ->first();

        return (float) ($row->solde ?? 0);
    }

    /**
     * @return array{solde_caisse: float, total_approvisionnements: int, total_montant_appro: float}
     */
    public function stats(): array
    {
        $appro = CaisseMouvement::query()
            ->where('type', CaisseMouvement::TYPE_APPROVISIONNEMENT)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(montant), 0) as total_montant')
            ->first();

        return [
            'solde_caisse' => $this->getSolde(),
            'total_approvisionnements' => (int) ($appro->total ?? 0),
            'total_montant_appro' => (float) ($appro->total_montant ?? 0),
        ];
    }

    public function createApprovisionnement(float $montant, string $source, ?User $user, ?string $motifs = null): CaisseMouvement
    {
        return DB::transaction(function () use ($montant, $source, $user, $motifs) {
            $solde = $this->getSolde() + $montant;

            return CaisseMouvement::create([
                'type' => CaisseMouvement::TYPE_APPROVISIONNEMENT,
                'montant' => $montant,
                'source' => $source !== '' ? $source : 'Manuel',
                'motifs' => $motifs ?: 'Approvisionnement de la caisse',
                'solde_apres' => $solde,
                'user_id' => $user?->id,
                'date_mouvement' => now(),
            ]);
        });
    }

    /**
     * @param  array{origine?: string, search?: string, date_debut?: string|null, date_fin?: string|null}  $filters
     */
    public function paginatedApprovisionnements(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = CaisseMouvement::query()
            ->with('user')
            ->where('type', CaisseMouvement::TYPE_APPROVISIONNEMENT)
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id');

        $origine = $filters['origine'] ?? 'all';
        if ($origine === 'manuel') {
            $query->where(function ($q) {
                $q->whereNull('source')
                    ->orWhere(function ($inner) {
                        $inner->where('source', 'not like', 'Usine:%')
                            ->where('source', 'not like', 'Banque:%');
                    });
            });
        } elseif ($origine === 'banque') {
            $query->where('source', 'like', 'Banque:%');
        } elseif ($origine === 'usine') {
            $query->where('source', 'like', 'Usine:%');
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                    ->orWhere('motifs', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_mouvement', '>=', $filters['date_debut']);
        }
        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_mouvement', '<=', $filters['date_fin']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
