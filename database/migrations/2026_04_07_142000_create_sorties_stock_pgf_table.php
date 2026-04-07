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
        Schema::create('sorties_stock_pgf', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_pgf_id');
            $table->unsignedBigInteger('fiche_sortie_id')->nullable();
            $table->unsignedBigInteger('id_pont');
            $table->string('nom_pont')->nullable();
            $table->string('code_pont')->nullable();
            $table->decimal('quantite', 15, 2);
            $table->date('date_sortie');
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->foreign('stock_pgf_id')->references('id')->on('stocks_pgf')->onDelete('cascade');
            $table->foreign('fiche_sortie_id')->references('id')->on('fiches_sortie')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sorties_stock_pgf');
    }
};
