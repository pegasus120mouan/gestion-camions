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
        Schema::create('fiches_sortie', function (Blueprint $table) {
            $table->id();
            $table->integer('vehicule_id');
            $table->string('matricule_vehicule', 50);
            $table->integer('id_pont');
            $table->string('nom_pont', 255)->nullable();
            $table->string('code_pont', 50)->nullable();
            $table->integer('id_agent');
            $table->string('nom_agent', 255)->nullable();
            $table->string('numero_agent', 50)->nullable();
            $table->date('date_chargement');
            $table->decimal('poids_pont', 12, 2);
            $table->decimal('total_depenses', 15, 2)->default(0);
            $table->timestamps();

            $table->index('vehicule_id');
            $table->index('id_pont');
            $table->index('id_agent');
            $table->index('date_chargement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiches_sortie');
    }
};
