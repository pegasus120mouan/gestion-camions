<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->string('paiement', 30)->default('non_paye')->after('statut');
        });

        DB::table('transferts')->whereNull('paiement')->orWhere('paiement', '')->update([
            'paiement' => 'non_paye',
        ]);
    }

    public function down(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->dropColumn('paiement');
        });
    }
};
