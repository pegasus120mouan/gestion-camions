<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bordereaux_transfert', function (Blueprint $table) {
            $table->id();
            $table->string('client_type', 20);
            $table->string('client_id', 50);
            $table->string('client_nom')->nullable();
            $table->string('client_code', 50)->nullable();
            $table->string('numero', 40)->unique();
            $table->date('date_generation');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('montant_total', 14, 2)->default(0);
            $table->decimal('montant_paye', 14, 2)->default(0);
            $table->decimal('poids_total', 14, 2)->default(0);
            $table->json('transferts_data')->nullable();
            $table->timestamps();

            $table->index(['client_type', 'client_id']);
        });

        Schema::table('transferts', function (Blueprint $table) {
            $table->foreignId('bordereau_transfert_id')
                ->nullable()
                ->after('paiement')
                ->constrained('bordereaux_transfert')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bordereau_transfert_id');
        });

        Schema::dropIfExists('bordereaux_transfert');
    }
};
