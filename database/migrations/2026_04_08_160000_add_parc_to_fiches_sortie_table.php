<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->unsignedBigInteger('parc_id')->nullable()->after('stock_id');
            $table->string('nom_parc')->nullable()->after('parc_id');
            $table->foreign('parc_id')->references('id')->on('parcs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropForeign(['parc_id']);
            $table->dropColumn(['parc_id', 'nom_parc']);
        });
    }
};
