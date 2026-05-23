<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pont');
            $table->string('nom_pont', 255)->nullable();
            $table->string('code_pont', 100)->nullable();
            $table->string('nom', 100);
            $table->string('prenom', 150);
            $table->string('contact', 50)->nullable();
            $table->string('code_pin');
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->timestamps();

            $table->index('id_pont');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commis');
    }
};
