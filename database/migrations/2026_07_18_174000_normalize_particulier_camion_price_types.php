<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('particulier_agent_prix')
            || ! Schema::hasColumn('particulier_agent_prix', 'type_transporteur')
        ) {
            return;
        }

        DB::table('particulier_agent_prix')
            ->whereNotNull('type_transporteur')
            ->whereRaw('LOWER(type_transporteur) LIKE ?', ['%pgf%'])
            ->update(['type_transporteur' => 'Camion PGF']);

        DB::table('particulier_agent_prix')
            ->whereNotNull('type_transporteur')
            ->whereRaw('LOWER(type_transporteur) NOT LIKE ?', ['%pgf%'])
            ->update(['type_transporteur' => 'Autre Camion']);
    }

    public function down(): void
    {
        // Les anciens libellés ne peuvent pas être reconstruits de façon fiable.
    }
};
