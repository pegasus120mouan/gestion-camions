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
        Schema::create('entrees_stock_pgf', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_pgf_id');
            $table->unsignedBigInteger('id_pont');
            $table->string('nom_pont')->nullable();
            $table->string('code_pont')->nullable();
            $table->decimal('quantite', 15, 2);
            $table->date('date_entree');
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->foreign('stock_pgf_id')->references('id')->on('stocks_pgf')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrees_stock_pgf');
    }
};
