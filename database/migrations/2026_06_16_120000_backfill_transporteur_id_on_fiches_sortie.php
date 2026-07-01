<?php

use App\Models\FicheSortie;
use App\Models\TransporteurVehicule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TransporteurVehicule::query()
            ->select(['transporteur_id', 'matricule_vehicule'])
            ->orderBy('id')
            ->get()
            ->each(function (TransporteurVehicule $link) {
                FicheSortie::query()
                    ->where('matricule_vehicule', $link->matricule_vehicule)
                    ->whereNull('transporteur_id')
                    ->update(['transporteur_id' => $link->transporteur_id]);
            });
    }

    public function down(): void
    {
        // Pas de retour arrière sur les données liées.
    }
};
