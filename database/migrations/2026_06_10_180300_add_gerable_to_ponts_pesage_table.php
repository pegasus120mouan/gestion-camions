<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ponts_pesage', function (Blueprint $table) {
            $table->boolean('gerable')->default(false)->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('ponts_pesage', function (Blueprint $table) {
            $table->dropColumn('gerable');
        });
    }
};
