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
            $table->integer('id_ticket')->nullable()->after('poids_pont');
            $table->string('numero_ticket', 100)->nullable()->after('id_ticket');
            $table->index('id_ticket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropIndex(['id_ticket']);
            $table->dropColumn(['id_ticket', 'numero_ticket']);
        });
    }
};
