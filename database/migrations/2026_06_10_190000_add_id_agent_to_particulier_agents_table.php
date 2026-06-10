<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('particulier_agents', function (Blueprint $table) {
            $table->unsignedBigInteger('id_agent')->nullable()->after('particulier_groupe_id');
        });
    }

    public function down(): void
    {
        Schema::table('particulier_agents', function (Blueprint $table) {
            $table->dropColumn('id_agent');
        });
    }
};
