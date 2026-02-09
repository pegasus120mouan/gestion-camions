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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('id_pont')->index();
            $table->string('code_pont', 50)->nullable();
            $table->string('nom_pont', 255)->nullable();
            $table->enum('type', ['entree', 'sortie'])->default('entree');
            $table->decimal('quantite', 12, 2)->default(0);
            $table->date('date_mouvement');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
