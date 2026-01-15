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
        // Migration volontairement neutralisée.
        // Le renommage (tables + colonnes) est fragile (contraintes FK, index, besoin possible de doctrine/dbal).
        // Les tables sont créées directement en français dans les migrations de création, puis on utilise migrate:fresh.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op
    }
};
