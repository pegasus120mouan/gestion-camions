<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usines', function (Blueprint $table) {
            $table->foreignId('produit_id')->nullable()->after('code_usine')->constrained('produits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('usines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produit_id');
        });
    }
};
