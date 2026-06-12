<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bordereaux_agent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_agent');
            $table->string('numero', 40)->unique();
            $table->string('agent_nom')->nullable();
            $table->string('agent_numero', 50)->nullable();
            $table->date('date_generation');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('montant_total', 14, 2)->default(0);
            $table->decimal('poids_total', 14, 2)->default(0);
            $table->json('fiches_data')->nullable();
            $table->timestamps();

            $table->index('id_agent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bordereaux_agent');
    }
};
