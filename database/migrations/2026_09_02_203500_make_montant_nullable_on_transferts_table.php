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
            $table->decimal('montant', 14, 2)->nullable()->default(null)->change();
        });

        DB::table('transferts')->where('montant', 0)->update(['montant' => null]);
    }

    public function down(): void
    {
        DB::table('transferts')->whereNull('montant')->update(['montant' => 0]);

        Schema::table('transferts', function (Blueprint $table) {
            $table->decimal('montant', 14, 2)->default(0)->nullable(false)->change();
        });
    }
};
