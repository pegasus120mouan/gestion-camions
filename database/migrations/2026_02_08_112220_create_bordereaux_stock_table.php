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
        Schema::create('bordereaux_stock', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('date_generation');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->json('ponts_data')->nullable();
            $table->decimal('poids_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bordereaux_stock');
    }
};
