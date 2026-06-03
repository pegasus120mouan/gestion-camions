<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('particulier_agent_prix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('particulier_agent_id')->constrained('particulier_agents')->onDelete('cascade');
            $table->unsignedBigInteger('id_usine');
            $table->string('nom_usine');
            $table->decimal('prix', 15, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->index(['particulier_agent_id', 'id_usine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('particulier_agent_prix');
    }
};
