<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->unsignedBigInteger('produit_id')->nullable()->after('usine');
            $table->string('nom_produit', 100)->nullable()->after('produit_id');
            
            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropForeign(['produit_id']);
            $table->dropColumn(['produit_id', 'nom_produit']);
        });
    }
};
