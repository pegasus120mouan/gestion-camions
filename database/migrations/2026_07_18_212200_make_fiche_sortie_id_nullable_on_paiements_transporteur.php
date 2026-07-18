<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Les paiements de bordereau n'ont pas de fiche_sortie_id.
        DB::statement('ALTER TABLE paiements_transporteur MODIFY fiche_sortie_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE paiements_transporteur MODIFY fiche_sortie_id BIGINT UNSIGNED NOT NULL');
    }
};
