<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('code_stock')->nullable()->after('id');
            $table->enum('statut', ['ouvert', 'ferme'])->default('ouvert')->after('quantite');
            $table->date('date_fermeture')->nullable()->after('statut');
            $table->text('commentaire')->nullable()->after('date_fermeture');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['code_stock', 'statut', 'date_fermeture', 'commentaire']);
        });
    }
};
