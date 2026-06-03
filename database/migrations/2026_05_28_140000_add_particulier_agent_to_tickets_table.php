<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicule_id')->nullable()->change();
            $table->unsignedBigInteger('id_agent')->nullable()->change();
            $table->foreignId('particulier_agent_id')
                ->nullable()
                ->after('id_agent')
                ->constrained('particulier_agents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['particulier_agent_id']);
            $table->dropColumn('particulier_agent_id');
            $table->unsignedBigInteger('vehicule_id')->nullable(false)->change();
            $table->unsignedBigInteger('id_agent')->nullable(false)->change();
        });
    }
};
