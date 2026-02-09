<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id('id_ticket');
            $table->unsignedBigInteger('id_usine');
            $table->date('date_ticket');
            $table->unsignedBigInteger('id_agent');
            $table->string('numero_ticket');
            $table->unsignedBigInteger('vehicule_id');
            $table->string('matricule_vehicule')->nullable();
            $table->float('poids')->nullable();
            $table->unsignedBigInteger('id_utilisateur');
            $table->decimal('prix_unitaire', 10, 2)->default(0);
            $table->datetime('date_validation_boss')->nullable();
            $table->decimal('montant_paie', 20, 2)->nullable();
            $table->decimal('montant_payer', 20, 2)->nullable();
            $table->decimal('montant_reste', 20, 2)->nullable();
            $table->datetime('date_paie')->nullable();
            $table->enum('statut_ticket', ['soldé', 'non soldé'])->default('non soldé');
            $table->string('numero_bordereau')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
