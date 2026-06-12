<?php

use App\Models\BordereauAgent;
use App\Models\FicheSortie;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->unsignedBigInteger('bordereau_agent_id')->nullable()->after('id_agent');
            $table->index('bordereau_agent_id');
        });

        BordereauAgent::query()->each(function (BordereauAgent $bordereau) {
            foreach ($bordereau->fiches_data ?? [] as $ligne) {
                $ficheId = (int) ($ligne['fiche_id'] ?? 0);
                if ($ficheId <= 0) {
                    continue;
                }

                FicheSortie::query()
                    ->where('id', $ficheId)
                    ->where('id_agent', $bordereau->id_agent)
                    ->update(['bordereau_agent_id' => $bordereau->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropIndex(['bordereau_agent_id']);
            $table->dropColumn('bordereau_agent_id');
        });
    }
};
