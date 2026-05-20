<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvisionnements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pont_id')->nullable();
            $table->string('nom_pont', 150)->nullable();
            $table->string('code_pont', 50)->nullable();
            $table->decimal('montant', 15, 2);
            $table->date('date_approvisionnement');
            $table->string('mode_paiement', 50)->nullable();
            $table->string('nom_banque', 100)->nullable();
            $table->string('numero_cheque', 50)->nullable();
            $table->string('operateur', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvisionnements');
    }
};
