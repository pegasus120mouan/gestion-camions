<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->foreignId('produit_id')
                ->nullable()
                ->after('client_id')
                ->constrained('produits')
                ->nullOnDelete();
            $table->string('nom_produit')->nullable()->after('produit_id');
        });
    }

    public function down(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produit_id');
            $table->dropColumn('nom_produit');
        });
    }
};
