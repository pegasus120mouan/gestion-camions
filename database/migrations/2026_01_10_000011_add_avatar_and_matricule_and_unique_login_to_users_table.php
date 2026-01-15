<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $table, $indexName]
        );

        return ! is_null($row);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'matricule')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('matricule')->nullable()->after('contact');
            });
        }

        if (! Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar')->nullable()->after('matricule');
            });
        }

        if (Schema::hasColumn('users', 'matricule') && ! $this->indexExists('users', 'users_matricule_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('matricule');
            });
        }

        if (! $this->indexExists('users', 'users_login_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('login');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('users', 'users_login_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['login']);
            });
        }

        if ($this->indexExists('users', 'users_matricule_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['matricule']);
            });
        }

        if (Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('avatar');
            });
        }

        if (Schema::hasColumn('users', 'matricule')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('matricule');
            });
        }
    }
};
