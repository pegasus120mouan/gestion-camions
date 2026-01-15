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
        Schema::table('pesees', function (Blueprint $table) {
            if (!Schema::hasColumn('pesees', 'status')) {
                $table->string('status', 20)->default('validated')->after('notes');
                $table->index(['status']);
            }

            if (!Schema::hasColumn('pesees', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('status');
            }

            if (!Schema::hasColumn('pesees', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancel_reason');
            }

            if (!Schema::hasColumn('pesees', 'cancelled_by')) {
                $table->foreignId('cancelled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('cancelled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesees', function (Blueprint $table) {
            if (Schema::hasColumn('pesees', 'cancelled_by')) {
                $table->dropConstrainedForeignId('cancelled_by');
            }

            if (Schema::hasColumn('pesees', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('pesees', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }

            if (Schema::hasColumn('pesees', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
