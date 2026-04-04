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
            $table->decimal('montant_paye_transporteur', 15, 2)->default(0)->after('prix_unitaire_transport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropColumn('montant_paye_transporteur');
        });
    }
};
