<?php

namespace App\Services;

use App\Models\Groupe;
use App\Models\GroupeVehicule;

class CamionPgfService
{
    /** @var array{ids: array<int, true>, matricules: array<string, true>}|null */
    private ?array $lookup = null;

    /**
     * @return array{ids: array<int, true>, matricules: array<string, true>}
     */
    public function lookup(): array
    {
        if ($this->lookup !== null) {
            return $this->lookup;
        }

        $groupeIds = Groupe::query()
            ->where('nom_groupe', 'like', '%PGF%')
            ->pluck('id');

        $rows = GroupeVehicule::query()
            ->whereIn('groupe_id', $groupeIds)
            ->get(['vehicule_id', 'matricule_vehicule']);

        $ids = [];
        $matricules = [];
        foreach ($rows as $row) {
            $id = (int) $row->vehicule_id;
            if ($id > 0) {
                $ids[$id] = true;
            }
            $matricule = strtoupper(trim((string) $row->matricule_vehicule));
            if ($matricule !== '') {
                $matricules[$matricule] = true;
            }
        }

        return $this->lookup = ['ids' => $ids, 'matricules' => $matricules];
    }

    /**
     * @param  array{ids: array<int, true>, matricules: array<string, true>}|null  $lookup
     */
    public function estCamionPgf(int $vehiculeId, ?string $matricule, ?array $lookup = null): bool
    {
        $lookup ??= $this->lookup();

        if ($vehiculeId > 0 && isset($lookup['ids'][$vehiculeId])) {
            return true;
        }

        $matricule = strtoupper(trim((string) $matricule));

        return $matricule !== '' && isset($lookup['matricules'][$matricule]);
    }
}
