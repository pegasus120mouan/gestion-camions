<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camion_etats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicule_id')->unique();
            $table->string('matricule', 100)->nullable();
            $table->string('etat', 30)->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camion_etats');
    }
};
