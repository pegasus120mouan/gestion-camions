<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 10, 2)->nullable()->after('quantite');
            $table->decimal('montant_total', 15, 2)->nullable()->after('prix_unitaire');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['prix_unitaire', 'montant_total']);
        });
    }
};
