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
        Schema::create('chef_chargeur_prix', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_chef_chargeur');
            $table->integer('prix_unitaire');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->foreign('id_chef_chargeur')->references('id')->on('chef_chargeurs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chef_chargeur_prix');
    }
};
