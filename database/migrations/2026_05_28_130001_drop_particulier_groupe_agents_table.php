<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('particulier_groupe_agents');
    }

    public function down(): void
    {
        Schema::create('particulier_groupe_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('particulier_groupe_id')->constrained('particulier_groupes')->onDelete('cascade');
            $table->unsignedBigInteger('id_agent');
            $table->timestamps();

            $table->unique(['particulier_groupe_id', 'id_agent']);
        });
    }
};
