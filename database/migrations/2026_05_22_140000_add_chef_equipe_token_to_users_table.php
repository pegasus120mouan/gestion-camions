<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'chef_equipe_token')) {
                $table->string('chef_equipe_token', 50)->nullable()->after('matricule');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'chef_equipe_token')) {
                $table->dropColumn('chef_equipe_token');
            }
        });
    }
};
