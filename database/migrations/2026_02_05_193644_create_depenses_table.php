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
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicule_id');
            $table->string('matricule_vehicule', 50);
            $table->string('type_depense', 100);
            $table->string('description', 500)->nullable();
            $table->decimal('montant', 15, 2)->default(0);
            $table->date('date_depense');
            $table->timestamps();

            $table->index('vehicule_id');
            $table->index('matricule_vehicule');
            $table->index('date_depense');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
