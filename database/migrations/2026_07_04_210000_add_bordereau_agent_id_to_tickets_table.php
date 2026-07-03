<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tickets') && ! Schema::hasColumn('tickets', 'bordereau_agent_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('bordereau_agent_id')->nullable()->after('conformite');
                $table->index('bordereau_agent_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'bordereau_agent_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex(['bordereau_agent_id']);
                $table->dropColumn('bordereau_agent_id');
            });
        }
    }
};
