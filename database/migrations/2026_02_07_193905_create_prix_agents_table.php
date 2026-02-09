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
        Schema::create('prix_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_agent');
            $table->unsignedBigInteger('id_usine');
            $table->string('nom_usine');
            $table->string('type')->default('transporteur'); // transporteur ou pgf
            $table->decimal('prix', 15, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();
            
            $table->index(['id_agent', 'id_usine', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prix_agents');
    }
};
