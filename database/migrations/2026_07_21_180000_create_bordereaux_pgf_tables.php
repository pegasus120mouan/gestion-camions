<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bordereaux_pgf')) {
            Schema::create('bordereaux_pgf', function (Blueprint $table) {
                $table->id();
                $table->string('numero', 40)->unique();
                $table->string('libelle', 100)->default('PGF');
                $table->date('date_generation');
                $table->date('date_debut');
                $table->date('date_fin');
                $table->decimal('montant_total', 14, 2)->default(0);
                $table->decimal('montant_paye', 14, 2)->default(0);
                $table->decimal('poids_total', 14, 2)->default(0);
                $table->json('fiches_data')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('paiements_pgf')) {
            Schema::create('paiements_pgf', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_bordereau')->nullable()->index();
                $table->decimal('montant', 14, 2);
                $table->date('date_paiement');
                $table->string('mode_paiement', 50)->nullable();
                $table->string('caisse', 30)->nullable();
                $table->string('reference', 100)->nullable();
                $table->string('commentaire', 500)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tickets') && ! Schema::hasColumn('tickets', 'bordereau_pgf_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('bordereau_pgf_id')->nullable()->after('bordereau_agent_id');
                $table->index('bordereau_pgf_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'bordereau_pgf_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex(['bordereau_pgf_id']);
                $table->dropColumn('bordereau_pgf_id');
            });
        }

        Schema::dropIfExists('paiements_pgf');
        Schema::dropIfExists('bordereaux_pgf');
    }
};
