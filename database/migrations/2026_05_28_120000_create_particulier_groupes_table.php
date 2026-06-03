<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('particulier_groupes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_groupe');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('particulier_groupes');
    }
};
