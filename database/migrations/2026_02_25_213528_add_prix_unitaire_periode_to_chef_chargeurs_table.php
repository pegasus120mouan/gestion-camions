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
        Schema::table('chef_chargeurs', function (Blueprint $table) {
            $table->integer('prix_unitaire')->nullable()->after('contact');
            $table->date('date_debut')->nullable()->after('prix_unitaire');
            $table->date('date_fin')->nullable()->after('date_debut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chef_chargeurs', function (Blueprint $table) {
            $table->dropColumn(['prix_unitaire', 'date_debut', 'date_fin']);
        });
    }
};
