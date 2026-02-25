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
            $table->string('usine')->nullable()->after('nom_pont');
            $table->date('date_dechargement')->nullable()->after('date_chargement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropColumn(['usine', 'date_dechargement']);
        });
    }
};
