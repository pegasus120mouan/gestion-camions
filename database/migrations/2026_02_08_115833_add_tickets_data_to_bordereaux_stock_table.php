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
        Schema::table('bordereaux_stock', function (Blueprint $table) {
            $table->json('tickets_data')->nullable()->after('ponts_data');
            $table->decimal('poids_sortie', 15, 2)->default(0)->after('poids_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bordereaux_stock', function (Blueprint $table) {
            $table->dropColumn(['tickets_data', 'poids_sortie']);
        });
    }
};
