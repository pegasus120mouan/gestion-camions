<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements_agent', function (Blueprint $table) {
            $table->unsignedBigInteger('id_bordereau')->nullable()->after('id_agent');
            $table->index('id_bordereau');
        });
    }

    public function down(): void
    {
        Schema::table('paiements_agent', function (Blueprint $table) {
            $table->dropIndex(['id_bordereau']);
            $table->dropColumn('id_bordereau');
        });
    }
};
