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
        Schema::table('particulier_agent_prix', function (Blueprint $table) {
            $table->unsignedBigInteger('produit_id')->nullable()->after('type_transporteur');
        });
    }

    public function down(): void
    {
        Schema::table('particulier_agent_prix', function (Blueprint $table) {
            $table->dropColumn('produit_id');
        });
    }
};
