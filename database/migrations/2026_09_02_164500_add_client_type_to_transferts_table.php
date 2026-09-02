<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->string('client_type', 20)->nullable()->after('client');
            $table->string('client_id', 50)->nullable()->after('client_type');
        });
    }

    public function down(): void
    {
        Schema::table('transferts', function (Blueprint $table) {
            $table->dropColumn(['client_type', 'client_id']);
        });
    }
};
