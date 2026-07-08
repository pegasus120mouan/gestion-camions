<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financement')) {
            return;
        }

        Schema::create('financement', function (Blueprint $table) {
            $table->unsignedBigInteger('Numero_financement')->autoIncrement();
            $table->unsignedBigInteger('id_agent');
            $table->decimal('montant', 15, 2);
            $table->string('motif', 1000)->nullable();
            $table->dateTime('date_financement')->nullable();
            $table->index('id_agent');
            $table->index('date_financement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financement');
    }
};
