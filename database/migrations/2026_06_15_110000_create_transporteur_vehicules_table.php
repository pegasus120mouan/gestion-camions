<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transporteur_vehicules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporteur_id')->constrained('transporteurs')->cascadeOnDelete();
            $table->unsignedBigInteger('vehicule_id');
            $table->string('matricule_vehicule', 50);
            $table->timestamps();

            $table->unique('vehicule_id');
            $table->unique(['transporteur_id', 'vehicule_id'], 'transporteur_vehicule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporteur_vehicules');
    }
};
