<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bordereaux_transporteur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transporteur_id');
            $table->string('numero', 40)->unique();
            $table->string('transporteur_nom')->nullable();
            $table->string('transporteur_code', 50)->nullable();
            $table->date('date_generation');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('montant_total', 14, 2)->default(0);
            $table->decimal('montant_paye', 14, 2)->default(0);
            $table->decimal('poids_total', 14, 2)->default(0);
            $table->json('fiches_data')->nullable();
            $table->timestamps();

            $table->index('transporteur_id');
        });

        if (Schema::hasTable('fiches_sortie') && ! Schema::hasColumn('fiches_sortie', 'bordereau_transporteur_id')) {
            Schema::table('fiches_sortie', function (Blueprint $table) {
                $table->unsignedBigInteger('bordereau_transporteur_id')->nullable()->after('bordereau_agent_id');
                $table->index('bordereau_transporteur_id');
            });
        }

        if (Schema::hasTable('paiements_transporteur') && ! Schema::hasColumn('paiements_transporteur', 'id_bordereau')) {
            Schema::table('paiements_transporteur', function (Blueprint $table) {
                $table->unsignedBigInteger('id_bordereau')->nullable()->after('fiche_sortie_id');
                $table->index('id_bordereau');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('paiements_transporteur') && Schema::hasColumn('paiements_transporteur', 'id_bordereau')) {
            Schema::table('paiements_transporteur', function (Blueprint $table) {
                $table->dropIndex(['id_bordereau']);
                $table->dropColumn('id_bordereau');
            });
        }

        if (Schema::hasTable('fiches_sortie') && Schema::hasColumn('fiches_sortie', 'bordereau_transporteur_id')) {
            Schema::table('fiches_sortie', function (Blueprint $table) {
                $table->dropIndex(['bordereau_transporteur_id']);
                $table->dropColumn('bordereau_transporteur_id');
            });
        }

        Schema::dropIfExists('bordereaux_transporteur');
    }
};
