<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bordereaux_agent', function (Blueprint $table) {
            $table->decimal('montant_paye', 14, 2)->default(0)->after('montant_total');
        });
    }

    public function down(): void
    {
        Schema::table('bordereaux_agent', function (Blueprint $table) {
            $table->dropColumn('montant_paye');
        });
    }
};
