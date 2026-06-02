<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->decimal('prix_unitaire_camion', 10, 2)->nullable()->after('poids_pont');
            $table->decimal('montant_camion', 15, 2)->nullable()->after('prix_unitaire_camion');
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropColumn(['prix_unitaire_camion', 'montant_camion']);
        });
    }
};
