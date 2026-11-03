<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfert_ponts', function (Blueprint $table) {
            $table->id();
            $table->date('date_transfert');
            $table->unsignedBigInteger('vehicule_id')->nullable();
            $table->string('matricule_vehicule', 50);

            $table->unsignedBigInteger('produit_id');
            $table->string('nom_produit')->nullable();

            $table->unsignedBigInteger('id_pont_source');
            $table->string('nom_pont_source')->nullable();
            $table->string('code_pont_source')->nullable();
            $table->unsignedBigInteger('parc_source_id')->nullable();
            $table->unsignedBigInteger('stock_source_id')->nullable();
            $table->string('nom_parc_source')->nullable();
            $table->decimal('poids_depart', 12, 2);

            $table->unsignedBigInteger('id_pont_destination');
            $table->string('nom_pont_destination')->nullable();
            $table->string('code_pont_destination')->nullable();
            $table->unsignedBigInteger('parc_destination_id')->nullable();
            $table->unsignedBigInteger('stock_destination_id')->nullable();
            $table->string('nom_parc_destination')->nullable();
            $table->decimal('poids_arrivee', 12, 2);

            $table->unsignedBigInteger('entree_stock_id')->nullable();
            $table->string('commentaire', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['date_transfert', 'id']);
            $table->index('produit_id');
            $table->index('id_pont_source');
            $table->index('id_pont_destination');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfert_ponts');
    }
};
