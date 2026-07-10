<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisse_mouvements', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // approvisionnement | paiement
            $table->decimal('montant', 15, 2);
            $table->string('source', 150)->nullable();
            $table->string('motifs', 255)->nullable();
            $table->decimal('solde_apres', 15, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_mouvement')->useCurrent();
            $table->timestamps();

            $table->index(['type', 'date_mouvement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_mouvements');
    }
};
