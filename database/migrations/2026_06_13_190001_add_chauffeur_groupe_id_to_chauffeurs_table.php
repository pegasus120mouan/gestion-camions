<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultGroupeId = (int) DB::table('chauffeur_groupes')
            ->where('nom_groupe', 'Chauffeurs PGF')
            ->value('id');

        Schema::table('chauffeurs', function (Blueprint $table) use ($defaultGroupeId) {
            $table->foreignId('chauffeur_groupe_id')
                ->default($defaultGroupeId)
                ->after('prenoms')
                ->constrained('chauffeur_groupes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chauffeurs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chauffeur_groupe_id');
        });
    }
};
