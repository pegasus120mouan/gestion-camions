<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pisteurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenoms', 150);
            $table->string('contact', 50)->nullable();
            $table->integer('prix_unitaire')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();
        });

        Schema::create('pisteur_prix', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pisteur');
            $table->integer('prix_unitaire');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->foreign('id_pisteur')->references('id')->on('pisteurs')->onDelete('cascade');
        });

        Schema::create('paiements_pisteur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pisteur');
            $table->integer('montant');
            $table->date('date_paiement');
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('commentaire', 500)->nullable();
            $table->timestamps();

            $table->foreign('id_pisteur')->references('id')->on('pisteurs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_pisteur');
        Schema::dropIfExists('pisteur_prix');
        Schema::dropIfExists('pisteurs');
    }
};
