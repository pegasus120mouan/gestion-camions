<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chauffeurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenoms', 150);
            $table->string('contact', 50)->nullable();
            $table->string('matricule_vehicule', 50)->nullable();
            $table->unsignedBigInteger('vehicule_id')->nullable();
            $table->decimal('salaire', 12, 2)->default(0);
            $table->timestamps();

            $table->index('matricule_vehicule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chauffeurs');
    }
};
