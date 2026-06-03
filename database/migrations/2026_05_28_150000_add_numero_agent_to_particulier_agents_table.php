<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('particulier_agents', function (Blueprint $table) {
            $table->string('numero_agent', 50)->nullable()->after('id');
        });

        $index = 1;
        foreach (DB::table('particulier_agents')->orderBy('id')->pluck('id') as $id) {
            DB::table('particulier_agents')->where('id', $id)->update([
                'numero_agent' => 'AGP-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            ]);
            $index++;
        }

        Schema::table('particulier_agents', function (Blueprint $table) {
            $table->unique('numero_agent');
        });
    }

    public function down(): void
    {
        Schema::table('particulier_agents', function (Blueprint $table) {
            $table->dropUnique(['numero_agent']);
            $table->dropColumn('numero_agent');
        });
    }
};
