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
        Schema::create('paiements_transporteur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fiche_sortie_id');
            $table->string('matricule_vehicule');
            $table->decimal('montant', 15, 2);
            $table->date('date_paiement');
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->foreign('fiche_sortie_id')->references('id')->on('fiches_sortie')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements_transporteur');
    }
};
