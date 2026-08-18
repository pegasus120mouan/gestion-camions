<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pont')->nullable()->after('id_usine');
            $table->string('nom_pont')->nullable()->after('id_pont');
        });

        // Reprendre le pont depuis les fiches déjà liées.
        if (Schema::hasTable('fiches_sortie')) {
            DB::statement("
                UPDATE tickets t
                INNER JOIN fiches_sortie f
                    ON (
                        (f.id_ticket IS NOT NULL AND f.id_ticket = t.id_ticket)
                        OR (
                            f.numero_ticket IS NOT NULL
                            AND f.numero_ticket <> ''
                            AND LOWER(TRIM(f.numero_ticket)) = LOWER(TRIM(t.numero_ticket))
                        )
                    )
                SET
                    t.id_pont = COALESCE(NULLIF(t.id_pont, 0), NULLIF(f.id_pont, 0)),
                    t.nom_pont = COALESCE(
                        NULLIF(TRIM(t.nom_pont), ''),
                        NULLIF(TRIM(f.nom_pont), '')
                    )
                WHERE (t.id_pont IS NULL OR t.id_pont = 0 OR t.nom_pont IS NULL OR TRIM(t.nom_pont) = '')
                  AND (
                      (f.id_pont IS NOT NULL AND f.id_pont > 0)
                      OR (f.nom_pont IS NOT NULL AND TRIM(f.nom_pont) <> '')
                  )
            ");
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['id_pont', 'nom_pont']);
        });
    }
};
