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
        Schema::create('paiements_chef_chargeur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_chef_chargeur');
            $table->integer('montant');
            $table->date('date_paiement');
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->foreign('id_chef_chargeur')->references('id')->on('chef_chargeurs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements_chef_chargeur');
    }
};
