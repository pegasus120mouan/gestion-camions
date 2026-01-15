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
        Schema::create('camions', function (Blueprint $table) {
            $table->id();
            $table->string('immatriculation')->unique();
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->unsignedInteger('annee')->nullable();

            $table->foreignId('chauffeur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['chauffeur_id']);
            $table->index(['actif']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camions');
    }
};
