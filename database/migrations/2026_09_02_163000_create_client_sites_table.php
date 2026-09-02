<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_sites', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 20); // usine | particulier
            $table->string('owner_id'); // id_usine or clients.id
            $table->string('owner_nom')->nullable();
            $table->string('nom');
            $table->string('adresse')->nullable();
            $table->string('contact')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_sites');
    }
};
