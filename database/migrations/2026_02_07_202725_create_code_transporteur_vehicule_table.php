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
        Schema::create('code_transporteur_vehicule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('code_transporteur_id');
            $table->unsignedBigInteger('vehicule_id');
            $table->string('matricule_vehicule');
            $table->timestamps();
            
            $table->foreign('code_transporteur_id')->references('id')->on('code_transporteurs')->onDelete('cascade');
            $table->unique(['code_transporteur_id', 'vehicule_id'], 'code_vehicule_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_transporteur_vehicule');
    }
};
