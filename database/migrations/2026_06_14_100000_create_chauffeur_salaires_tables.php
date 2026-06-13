<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chauffeur_salaire_periodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
            $table->unsignedSmallInteger('annee');
            $table->unsignedTinyInteger('mois');
            $table->decimal('montant_salaire', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['chauffeur_id', 'annee', 'mois']);
            $table->index(['annee', 'mois']);
        });

        Schema::create('chauffeur_salaire_avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
            $table->foreignId('chauffeur_salaire_periode_id')
                ->nullable()
                ->constrained('chauffeur_salaire_periodes')
                ->nullOnDelete();
            $table->date('date_avance');
            $table->decimal('montant', 12, 2);
            $table->string('libelle', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('chauffeur_salaire_paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
            $table->date('date_paiement');
            $table->decimal('montant_total', 12, 2);
            $table->string('libelle', 255)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });

        Schema::create('chauffeur_salaire_paiement_periodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chauffeur_salaire_paiement_id');
            $table->unsignedBigInteger('chauffeur_salaire_periode_id');
            $table->decimal('montant', 12, 2);
            $table->timestamps();

            $table->foreign('chauffeur_salaire_paiement_id', 'fk_cspp_paiement')
                ->references('id')->on('chauffeur_salaire_paiements')->cascadeOnDelete();
            $table->foreign('chauffeur_salaire_periode_id', 'fk_cspp_periode')
                ->references('id')->on('chauffeur_salaire_periodes')->cascadeOnDelete();
            $table->unique(
                ['chauffeur_salaire_paiement_id', 'chauffeur_salaire_periode_id'],
                'csp_paiement_periode_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chauffeur_salaire_paiement_periodes');
        Schema::dropIfExists('chauffeur_salaire_paiements');
        Schema::dropIfExists('chauffeur_salaire_avances');
        Schema::dropIfExists('chauffeur_salaire_periodes');
    }
};
