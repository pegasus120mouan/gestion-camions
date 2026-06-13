<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements_agent', function (Blueprint $table) {
            $table->string('numero_recu', 20)->nullable()->unique()->after('id');
        });

        foreach (DB::table('paiements_agent')->orderBy('id')->get() as $row) {
            $date = $row->date_paiement ?? now()->format('Y-m-d');
            $numero = date('Ymd', strtotime((string) $date)) . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);
            DB::table('paiements_agent')->where('id', $row->id)->update(['numero_recu' => $numero]);
        }
    }

    public function down(): void
    {
        Schema::table('paiements_agent', function (Blueprint $table) {
            $table->dropColumn('numero_recu');
        });
    }
};
