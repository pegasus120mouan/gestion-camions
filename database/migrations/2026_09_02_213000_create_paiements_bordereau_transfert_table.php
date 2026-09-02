<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements_bordereau_transfert', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bordereau_transfert_id')
                ->constrained('bordereaux_transfert')
                ->cascadeOnDelete();
            $table->string('client_type', 20);
            $table->string('client_id', 50);
            $table->decimal('montant', 14, 2);
            $table->date('date_paiement');
            $table->string('observation')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_type', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_bordereau_transfert');
    }
};
