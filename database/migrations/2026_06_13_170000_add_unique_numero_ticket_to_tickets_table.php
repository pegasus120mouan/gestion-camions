<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('tickets')
            ->select('numero_ticket', DB::raw('MIN(id_ticket) as keep_id'))
            ->groupBy('numero_ticket')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $removeIds = DB::table('tickets')
                ->where('numero_ticket', $duplicate->numero_ticket)
                ->where('id_ticket', '!=', $duplicate->keep_id)
                ->pluck('id_ticket');

            foreach ($removeIds as $removeId) {
                DB::table('fiches_sortie')->where('id_ticket', $removeId)->delete();
                DB::table('tickets')->where('id_ticket', $removeId)->delete();
            }
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->unique('numero_ticket');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['numero_ticket']);
        });
    }
};
