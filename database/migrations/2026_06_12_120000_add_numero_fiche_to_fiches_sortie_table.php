<?php

use App\Models\FicheSortie;
use App\Services\FicheSortieNumeroService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->string('numero_fiche', 30)->nullable()->unique()->after('id');
        });

        $service = app(FicheSortieNumeroService::class);
        $counters = [];

        FicheSortie::query()->orderBy('id')->each(function (FicheSortie $fiche) use ($service, &$counters) {
            if ($fiche->numero_fiche) {
                return;
            }

            $lettres = $service->lettresPont($fiche->nom_pont ?? '');
            $prefix = 'FICH-' . $lettres;
            $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;
            $fiche->numero_fiche = $prefix . $counters[$prefix];
            $fiche->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('fiches_sortie', function (Blueprint $table) {
            $table->dropUnique(['numero_fiche']);
            $table->dropColumn('numero_fiche');
        });
    }
};
