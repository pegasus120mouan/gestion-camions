<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pisteur')->nullable()->after('id_chef_chargeur');
            $table->foreign('id_pisteur')->references('id')->on('pisteurs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropForeign(['id_pisteur']);
            $table->dropColumn('id_pisteur');
        });
    }
};
