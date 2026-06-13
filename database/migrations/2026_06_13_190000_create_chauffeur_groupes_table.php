<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chauffeur_groupes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_groupe', 100);
            $table->timestamps();
        });

        DB::table('chauffeur_groupes')->insert([
            [
                'nom_groupe' => 'Chauffeurs PGF',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_groupe' => 'Autres Chauffeurs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chauffeur_groupes');
    }
};
