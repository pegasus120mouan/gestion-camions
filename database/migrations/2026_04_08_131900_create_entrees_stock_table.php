<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrees_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            $table->decimal('quantite', 15, 2);
            $table->date('date_entree');
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrees_stock');
    }
};
