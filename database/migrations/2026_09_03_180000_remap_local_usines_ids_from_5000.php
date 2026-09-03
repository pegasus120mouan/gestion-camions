<?php

use App\Models\Usine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOCAL_ID_MIN = 5000;

    private const TEMP_OFFSET = 90000;

    public function up(): void
    {
        if (! Schema::hasTable('usines')) {
            return;
        }

        $usines = DB::table('usines')->orderBy('id_usine')->get();
        $toRemap = $usines->filter(fn ($u) => (int) $u->id_usine < self::LOCAL_ID_MIN)->values();

        if ($toRemap->isEmpty()) {
            $this->ensureAutoIncrement();

            return;
        }

        $map = [];
        $next = self::LOCAL_ID_MIN;
        $existing = $usines->pluck('id_usine')->map(fn ($id) => (int) $id)->all();
        while (in_array($next, $existing, true)) {
            $next++;
        }

        foreach ($toRemap as $usine) {
            $old = (int) $usine->id_usine;
            while (in_array($next, $existing, true) || in_array($next, $map, true)) {
                $next++;
            }
            $map[$old] = $next;
            $existing[] = $next;
            $next++;
        }

        DB::transaction(function () use ($map, $toRemap) {
            // Phase 1 : ids temporaires pour éviter les collisions de PK
            foreach ($map as $old => $final) {
                $temp = self::TEMP_OFFSET + $old;
                $this->updateUsineId($old, $temp, $toRemap->firstWhere('id_usine', $old)?->nom_usine);
            }

            // Phase 2 : ids définitifs >= 5000
            foreach ($map as $old => $final) {
                $temp = self::TEMP_OFFSET + $old;
                $nom = $toRemap->firstWhere('id_usine', $old)?->nom_usine;
                $this->updateUsineId($temp, $final, $nom);
            }
        });

        $this->ensureAutoIncrement();
    }

    public function down(): void
    {
        // Irreversible volontairement : les ids locaux restent dans l'espace >= 5000.
    }

    private function updateUsineId(int $from, int $to, ?string $nomUsine): void
    {
        if ($from === $to) {
            return;
        }

        DB::table('usines')->where('id_usine', $from)->update(['id_usine' => $to]);

        // Références locales (prix) — ne pas toucher tickets.id_usine (espace API)
        if (Schema::hasTable('prix_agents') && Schema::hasColumn('prix_agents', 'id_usine')) {
            DB::table('prix_agents')->where('id_usine', $from)->update(['id_usine' => $to]);
        }

        if (Schema::hasTable('particulier_agent_prix') && Schema::hasColumn('particulier_agent_prix', 'id_usine')) {
            DB::table('particulier_agent_prix')->where('id_usine', $from)->update(['id_usine' => $to]);
        }

        // Transferts financiers liés à une usine locale
        if (Schema::hasTable('transferts') && Schema::hasColumn('transferts', 'client_id')) {
            DB::table('transferts')
                ->where('client_type', 'usine')
                ->where('client_id', (string) $from)
                ->update(['client_id' => (string) $to]);
        }

        if (Schema::hasTable('bordereaux_transfert')) {
            DB::table('bordereaux_transfert')
                ->where('client_type', 'usine')
                ->where('client_id', (string) $from)
                ->update(['client_id' => (string) $to]);
        }

        if (Schema::hasTable('paiements_bordereau_transfert')) {
            DB::table('paiements_bordereau_transfert')
                ->where('client_type', 'usine')
                ->where('client_id', (string) $from)
                ->update(['client_id' => (string) $to]);
        }

        if (Schema::hasTable('client_sites') && Schema::hasColumn('client_sites', 'owner_id')) {
            DB::table('client_sites')
                ->where('owner_type', 'usine')
                ->where('owner_id', (string) $from)
                ->update(['owner_id' => (string) $to]);
        }
    }

    private function ensureAutoIncrement(): void
    {
        $max = (int) (DB::table('usines')->max('id_usine') ?? 0);
        $next = max(self::LOCAL_ID_MIN, $max + 1);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE usines AUTO_INCREMENT = ' . $next);
        }
    }
};
