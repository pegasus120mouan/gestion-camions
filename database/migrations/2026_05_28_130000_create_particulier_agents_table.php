<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('particulier_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('particulier_groupe_id')->constrained('particulier_groupes')->onDelete('cascade');
            $table->string('nom', 100);
            $table->string('prenoms', 150);
            $table->string('contact', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('particulier_agents');
    }
};
