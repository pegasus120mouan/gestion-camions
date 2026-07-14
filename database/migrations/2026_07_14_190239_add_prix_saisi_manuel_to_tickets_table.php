<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'prix_saisi_manuel')) {
                $table->boolean('prix_saisi_manuel')->default(false)->after('prix_unitaire');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'prix_saisi_manuel')) {
                $table->dropColumn('prix_saisi_manuel');
            }
        });
    }
};
