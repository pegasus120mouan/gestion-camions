<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->foreignId('transporteur_id')
                ->nullable()
                ->after('matricule_vehicule')
                ->constrained('transporteurs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transporteur_id');
        });
    }
};
