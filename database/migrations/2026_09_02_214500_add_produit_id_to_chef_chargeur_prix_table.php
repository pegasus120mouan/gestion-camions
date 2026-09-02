<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chef_chargeur_prix', function (Blueprint $table) {
            $table->foreignId('produit_id')
                ->nullable()
                ->after('id_chef_chargeur')
                ->constrained('produits')
                ->nullOnDelete();
            $table->string('nom_produit')->nullable()->after('produit_id');
            $table->index(['id_chef_chargeur', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::table('chef_chargeur_prix', function (Blueprint $table) {
            $table->dropIndex(['id_chef_chargeur', 'produit_id']);
            $table->dropConstrainedForeignId('produit_id');
            $table->dropColumn('nom_produit');
        });
    }
};
