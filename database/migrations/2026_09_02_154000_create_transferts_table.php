<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();
            $table->date('date_chargement');
            $table->unsignedBigInteger('vehicule_id')->nullable();
            $table->string('matricule_vehicule');
            $table->string('client');
            $table->string('lieu_depart');
            $table->string('lieu_destination');
            $table->decimal('poids_depart', 12, 2)->nullable();
            $table->decimal('poids_arrivee', 12, 2)->nullable();
            $table->decimal('montant', 14, 2)->default(0);
            $table->text('commentaire')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('date_chargement');
            $table->index('matricule_vehicule');
            $table->index('client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
