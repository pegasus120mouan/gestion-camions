<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_ticket')->unique();
            $table->string('numero_ticket')->index();
            $table->timestamp('validated_at');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'conformite')) {
            $legacy = DB::table('tickets')
                ->where('conformite', 'valide')
                ->get(['id_ticket', 'numero_ticket', 'updated_at', 'id_utilisateur']);

            foreach ($legacy as $row) {
                DB::table('ticket_validations')->insertOrIgnore([
                    'id_ticket' => $row->id_ticket,
                    'numero_ticket' => (string) ($row->numero_ticket ?? ''),
                    'validated_at' => $row->updated_at ?? now(),
                    'validated_by' => $row->id_utilisateur,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_validations');
    }
};
