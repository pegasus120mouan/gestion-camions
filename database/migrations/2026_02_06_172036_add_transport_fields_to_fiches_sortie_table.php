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
            $table->decimal('prix_unitaire_transport', 12, 2)->nullable()->after('numero_ticket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropColumn('prix_unitaire_transport');
        });
    }
};
