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
        Schema::create('agent_pont_pesage', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pont_pesage_id')
                ->constrained('ponts_pesage')
                ->cascadeOnDelete();

            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('affecte_le')->useCurrent();
            $table->timestamps();

            $table->unique(['pont_pesage_id', 'agent_id']);
            $table->index(['agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_pont_pesage');
    }
};
