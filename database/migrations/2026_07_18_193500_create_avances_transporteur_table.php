<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avances_transporteur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporteur_id')->constrained('transporteurs')->cascadeOnDelete();
            $table->unsignedBigInteger('montant');
            $table->date('date_avance');
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['transporteur_id', 'date_avance']);
        });

        if (! Schema::hasTable('paiements_transporteur_gestion')) {
            return;
        }

        $anciennesAvances = DB::table('paiements_transporteur_gestion')
            ->whereRaw('LOWER(COALESCE(commentaire, \'\')) LIKE ?', ['%avance%'])
            ->get();

        foreach ($anciennesAvances as $avance) {
            DB::table('avances_transporteur')->insert([
                'transporteur_id' => $avance->transporteur_id,
                'montant' => $avance->montant,
                'date_avance' => $avance->date_paiement,
                'mode_paiement' => $avance->mode_paiement,
                'reference' => $avance->reference,
                'commentaire' => $avance->commentaire,
                'created_at' => $avance->created_at,
                'updated_at' => $avance->updated_at,
            ]);
        }

        if ($anciennesAvances->isNotEmpty()) {
            DB::table('paiements_transporteur_gestion')
                ->whereIn('id', $anciennesAvances->pluck('id')->all())
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('avances_transporteur');
    }
};
