<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pesees', function (Blueprint $table) {
            if (!Schema::hasColumn('pesees', 'produit_id')) {
                $table->foreignId('produit_id')
                    ->nullable()
                    ->constrained('produits')
                    ->restrictOnDelete();
            }

            if (!Schema::hasColumn('pesees', 'poids_apres_refraction')) {
                $table->decimal('poids_apres_refraction', 10, 3, true)->nullable()->default(0)->after('tare');
            }

            if (!Schema::hasColumn('pesees', 'poids_vide')) {
                $table->decimal('poids_vide', 10, 3, true)->nullable()->after('poids_apres_refraction');
            }
        });

        if (Schema::hasColumn('pesees', 'poids_net')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE pesees MODIFY poids_net DECIMAL(10,3) UNSIGNED NULL');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasColumn('pesees', 'poids_net')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE pesees MODIFY poids_net DECIMAL(10,3) UNSIGNED NOT NULL');
            }
        }

        Schema::table('pesees', function (Blueprint $table) {
            if (Schema::hasColumn('pesees', 'produit_id')) {
                $table->dropConstrainedForeignId('produit_id');
            }

            if (Schema::hasColumn('pesees', 'poids_vide')) {
                $table->dropColumn('poids_vide');
            }

            if (Schema::hasColumn('pesees', 'poids_apres_refraction')) {
                $table->dropColumn('poids_apres_refraction');
            }
        });
    }
};
