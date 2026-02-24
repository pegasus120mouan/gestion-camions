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
        Schema::create('groupe_vehicules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('groupe_id');
            $table->unsignedBigInteger('vehicule_id');
            $table->string('matricule_vehicule', 50);
            $table->timestamps();

            $table->foreign('groupe_id')->references('id')->on('groupes')->onDelete('cascade');
            $table->unique(['groupe_id', 'vehicule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupe_vehicules');
    }
};
