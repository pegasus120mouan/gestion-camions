<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements_transporteur_gestion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporteur_id')->constrained('transporteurs')->cascadeOnDelete();
            $table->integer('montant');
            $table->date('date_paiement');
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_transporteur_gestion');
    }
};
