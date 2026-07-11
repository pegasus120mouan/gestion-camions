<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_avance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_agent');
            $table->string('agent_nom')->nullable();
            $table->string('agent_numero')->nullable();
            $table->decimal('montant', 15, 2);
            $table->date('date_demande');
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('commentaire', 500)->nullable();
            $table->string('source', 20)->default('api'); // local | api
            $table->string('statut', 30)->default('en_attente'); // en_attente | payee | annulee
            $table->unsignedBigInteger('paiement_agent_id')->nullable();
            $table->timestamp('payee_at')->nullable();
            $table->string('payee_par')->nullable();
            $table->timestamps();

            $table->index(['id_agent', 'statut']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_avance');
    }
};
