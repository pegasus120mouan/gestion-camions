<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            if (! Schema::hasColumn('fiches_sortie', 'type_fiche')) {
                $table->string('type_fiche', 20)->default('normale')->after('numero_fiche');
            }
            if (! Schema::hasColumn('fiches_sortie', 'statut_groupe')) {
                $table->string('statut_groupe', 20)->nullable()->after('type_fiche');
            }
            if (! Schema::hasColumn('fiches_sortie', 'localisation_type')) {
                $table->string('localisation_type', 20)->nullable()->after('statut_groupe');
            }
            if (! Schema::hasColumn('fiches_sortie', 'localisation_parc_id')) {
                $table->unsignedBigInteger('localisation_parc_id')->nullable()->after('localisation_type');
            }
            if (! Schema::hasColumn('fiches_sortie', 'localisation_stock_id')) {
                $table->unsignedBigInteger('localisation_stock_id')->nullable()->after('localisation_parc_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            foreach (['localisation_stock_id', 'localisation_parc_id', 'localisation_type', 'statut_groupe', 'type_fiche'] as $col) {
                if (Schema::hasColumn('fiches_sortie', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
