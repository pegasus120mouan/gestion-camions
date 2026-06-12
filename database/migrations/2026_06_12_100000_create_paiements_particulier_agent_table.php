<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements_particulier_agent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('particulier_agent_id')->constrained('particulier_agents')->onDelete('cascade');
            $table->integer('montant');
            $table->date('date_paiement');
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index('particulier_agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_particulier_agent');
    }
};
