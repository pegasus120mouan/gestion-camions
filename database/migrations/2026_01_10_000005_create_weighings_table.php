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
        Schema::create('pesees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pont_pesage_id')
                ->constrained('ponts_pesage')
                ->restrictOnDelete();

            $table->foreignId('camion_id')
                ->constrained('camions')
                ->restrictOnDelete();

            $table->foreignId('agent_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('chauffeur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('poids_brut', 10, 3, true);
            $table->decimal('tare', 10, 3, true);
            $table->decimal('poids_net', 10, 3, true);

            $table->timestamp('pese_le')->useCurrent();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['pese_le']);
            $table->index(['pont_pesage_id', 'pese_le']);
            $table->index(['camion_id', 'pese_le']);
            $table->index(['agent_id', 'pese_le']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesees');
    }
};
