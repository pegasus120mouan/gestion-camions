<?php

use App\Models\Camion;
use Carbon\Carbon;
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
        Schema::table('camions', function (Blueprint $table) {
            if (!Schema::hasColumn('camions', 'reference')) {
                $table->string('reference')->nullable()->after('immatriculation');
            }
        });

        Camion::query()
            ->whereNull('reference')
            ->orWhere('reference', '')
            ->orderBy('id')
            ->chunkById(200, function ($camions) {
                foreach ($camions as $camion) {
                    $prefix = 'CAM-';
                    $stamp = Carbon::now()->format('YmdHis');

                    do {
                        $candidate = $prefix . $stamp . '-' . random_int(100, 999);
                    } while (Camion::query()->where('reference', $candidate)->exists());

                    $camion->forceFill(['reference' => $candidate])->save();
                }
            });

        Schema::table('camions', function (Blueprint $table) {
            if (Schema::hasColumn('camions', 'reference')) {
                $table->unique('reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camions', function (Blueprint $table) {
            if (Schema::hasColumn('camions', 'reference')) {
                $table->dropUnique(['reference']);
                $table->dropColumn('reference');
            }
        });
    }
};
