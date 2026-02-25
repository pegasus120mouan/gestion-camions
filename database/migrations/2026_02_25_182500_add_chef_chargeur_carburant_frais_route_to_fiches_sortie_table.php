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
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->unsignedBigInteger('id_chef_chargeur')->nullable()->after('numero_agent');
            $table->integer('carburant')->nullable()->after('poids_pont');
            $table->integer('frais_route')->nullable()->after('carburant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropColumn(['id_chef_chargeur', 'carburant', 'frais_route']);
        });
    }
};
