<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pont_etats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pont')->unique();
            $table->string('nom_pont', 255)->nullable();
            $table->string('code_pont', 100)->nullable();
            $table->string('etat', 20)->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pont_etats');
    }
};
