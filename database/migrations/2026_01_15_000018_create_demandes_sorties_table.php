<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demandes_sorties', function (Blueprint $table) {
            $table->increments('id_demande');
            $table->string('numero_demande', 50);
            $table->dateTime('date_demande');
            $table->decimal('montant', 20, 2);
            $table->text('motif');
            $table->enum('statut', ['en_attente', 'approuve', 'rejete', 'paye'])->default('en_attente');
            $table->timestamp('date_approbation')->nullable();
            $table->unsignedBigInteger('approuve_par')->nullable();
            $table->dateTime('date_paiement')->nullable();
            $table->unsignedBigInteger('paye_par')->nullable();
            $table->decimal('montant_payer', 20, 2)->nullable();
            $table->decimal('montant_reste', 20, 2)->nullable();
            $table->timestamps();

            $table->unique('numero_demande');

            $table->foreign('approuve_par')->references('id')->on('users')->nullOnDelete();
            $table->foreign('paye_par')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_sorties');
    }
};
