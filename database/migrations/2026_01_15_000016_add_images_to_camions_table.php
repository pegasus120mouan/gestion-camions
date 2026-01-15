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
        Schema::table('camions', function (Blueprint $table) {
            if (!Schema::hasColumn('camions', 'image_face')) {
                $table->string('image_face')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('camions', 'image_profil_gauche')) {
                $table->string('image_profil_gauche')->nullable()->after('image_face');
            }
            if (!Schema::hasColumn('camions', 'image_profil_droit')) {
                $table->string('image_profil_droit')->nullable()->after('image_profil_gauche');
            }
            if (!Schema::hasColumn('camions', 'image_arriere')) {
                $table->string('image_arriere')->nullable()->after('image_profil_droit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camions', function (Blueprint $table) {
            if (Schema::hasColumn('camions', 'image_arriere')) {
                $table->dropColumn('image_arriere');
            }
            if (Schema::hasColumn('camions', 'image_profil_droit')) {
                $table->dropColumn('image_profil_droit');
            }
            if (Schema::hasColumn('camions', 'image_profil_gauche')) {
                $table->dropColumn('image_profil_gauche');
            }
            if (Schema::hasColumn('camions', 'image_face')) {
                $table->dropColumn('image_face');
            }
        });
    }
};
