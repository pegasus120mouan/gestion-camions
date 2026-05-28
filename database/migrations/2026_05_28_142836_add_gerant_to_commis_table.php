<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('commis', function (Blueprint $table) {
            if (!Schema::hasColumn('commis', 'gerant')) {
                $table->string('gerant', 255)->nullable()->after('code_pont');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commis', function (Blueprint $table) {
            if (Schema::hasColumn('commis', 'gerant')) {
                $table->dropColumn('gerant');
            }
        });
    }
};
