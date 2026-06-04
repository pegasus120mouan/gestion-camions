<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prix_agents', function (Blueprint $table) {
            $table->foreignId('produit_id')->nullable()->after('nom_usine')->constrained('produits')->nullOnDelete();
            $table->string('nom_produit')->nullable()->after('produit_id');
            $table->index(['id_agent', 'produit_id', 'nom_usine', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('prix_agents', function (Blueprint $table) {
            $table->dropIndex(['id_agent', 'produit_id', 'nom_usine', 'type']);
            $table->dropConstrainedForeignId('produit_id');
            $table->dropColumn('nom_produit');
        });
    }
};
