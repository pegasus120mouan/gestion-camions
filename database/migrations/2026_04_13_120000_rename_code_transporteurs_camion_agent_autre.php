<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('code_transporteurs')
            ->where('nom', 'Camion Agent')
            ->update(['nom' => 'camion Pisteur']);

        DB::table('code_transporteurs')
            ->where('nom', 'Autre')
            ->update(['nom' => 'Autre Camion']);
    }

    public function down(): void
    {
        DB::table('code_transporteurs')
            ->where('nom', 'camion Pisteur')
            ->update(['nom' => 'Camion Agent']);

        DB::table('code_transporteurs')
            ->where('nom', 'Autre Camion')
            ->update(['nom' => 'Autre']);
    }
};
