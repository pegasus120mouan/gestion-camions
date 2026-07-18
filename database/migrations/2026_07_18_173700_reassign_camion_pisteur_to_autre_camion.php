<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('code_transporteurs') || ! Schema::hasTable('code_transporteur_vehicule')) {
            return;
        }

        $pisteurIds = DB::table('code_transporteurs')
            ->whereRaw('LOWER(nom) LIKE ?', ['%pisteur%'])
            ->pluck('id');

        if ($pisteurIds->isEmpty()) {
            return;
        }

        $autreCamionId = DB::table('code_transporteurs')
            ->whereRaw('LOWER(nom) LIKE ?', ['%autre camion%'])
            ->value('id');

        if (! $autreCamionId) {
            $autreCamionId = DB::table('code_transporteurs')->insertGetId([
                'nom' => 'Autre Camion',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $dejaAttribues = DB::table('code_transporteur_vehicule')
            ->where('code_transporteur_id', $autreCamionId)
            ->pluck('vehicule_id')
            ->all();

        DB::table('code_transporteur_vehicule')
            ->whereIn('code_transporteur_id', $pisteurIds->all())
            ->whereNotIn('vehicule_id', $dejaAttribues)
            ->update([
                'code_transporteur_id' => $autreCamionId,
                'updated_at' => now(),
            ]);

        DB::table('code_transporteur_vehicule')
            ->whereIn('code_transporteur_id', $pisteurIds->all())
            ->delete();
    }

    public function down(): void
    {
        // Irreversible : les véhicules pisteur ont été fusionnés dans Autre Camion.
    }
};
