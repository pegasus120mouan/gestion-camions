<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapes_transfert_groupe', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fiche_sortie_id');
            $table->unsignedInteger('ordre')->default(1);
            $table->string('type_etape', 30); // chargement|transfert_pont|cloture_usine
            $table->unsignedBigInteger('id_agent')->nullable();
            $table->string('nom_agent')->nullable();
            $table->string('numero_agent', 50)->nullable();
            $table->unsignedBigInteger('vehicule_id')->nullable();
            $table->string('matricule_vehicule', 50)->nullable();
            $table->decimal('poids', 14, 2)->default(0);
            $table->unsignedBigInteger('parc_source_id')->nullable();
            $table->unsignedBigInteger('stock_source_id')->nullable();
            $table->string('nom_parc_source')->nullable();
            $table->unsignedBigInteger('id_pont_destination')->nullable();
            $table->string('nom_pont_destination')->nullable();
            $table->string('code_pont_destination', 50)->nullable();
            $table->unsignedBigInteger('parc_destination_id')->nullable();
            $table->unsignedBigInteger('stock_destination_id')->nullable();
            $table->string('nom_parc_destination')->nullable();
            $table->unsignedBigInteger('entree_stock_id')->nullable();
            $table->string('usine_destination')->nullable();
            $table->date('date_etape');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->foreign('fiche_sortie_id')
                ->references('id')
                ->on('fiches_sortie')
                ->onDelete('cascade');
            $table->index(['fiche_sortie_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapes_transfert_groupe');
    }
};
