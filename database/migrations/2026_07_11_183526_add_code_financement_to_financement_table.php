<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financement')) {
            return;
        }

        if (! Schema::hasColumn('financement', 'code_financement')) {
            Schema::table('financement', function (Blueprint $table) {
                $table->string('code_financement', 40)->nullable()->after('Numero_financement');
            });
        }

        try {
            Schema::table('financement', function (Blueprint $table) {
                $table->unique('code_financement');
            });
        } catch (\Throwable) {
            // index already exists
        }

        $agentColumns = Schema::hasTable('agents')
            ? Schema::getColumnListing('agents')
            : [];

        $select = ['f.Numero_financement', 'f.id_agent'];
        $query = DB::table('financement as f');

        if ($agentColumns !== []) {
            $query->leftJoin('agents as a', 'a.id_agent', '=', 'f.id_agent');
            foreach (['nom_complet', 'nom', 'prenom', 'nom_agent', 'prenom_agent'] as $col) {
                if (in_array($col, $agentColumns, true)) {
                    $select[] = 'a.'.$col;
                }
            }
        }

        $rows = $query->orderBy('f.Numero_financement')->get($select);
        $sequences = [];

        foreach ($rows as $row) {
            $nomComplet = trim((string) ($row->nom_complet ?? ''));
            if ($nomComplet === '') {
                $nom = trim((string) ($row->nom ?? $row->nom_agent ?? ''));
                $prenom = trim((string) ($row->prenom ?? $row->prenom_agent ?? ''));
                $nomComplet = trim($nom.' '.$prenom);
            }

            $initials = $this->initials($nomComplet);
            $prefix = 'FIN-'.$initials;
            $sequences[$prefix] = ($sequences[$prefix] ?? 0) + 1;
            $code = $prefix.'-'.sprintf('%04d', $sequences[$prefix]);

            DB::table('financement')
                ->where('Numero_financement', $row->Numero_financement)
                ->update(['code_financement' => $code]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('financement') || ! Schema::hasColumn('financement', 'code_financement')) {
            return;
        }

        Schema::table('financement', function (Blueprint $table) {
            try {
                $table->dropUnique(['code_financement']);
            } catch (\Throwable) {
            }
            $table->dropColumn('code_financement');
        });
    }

    private function initials(string $nomComplet): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/u', trim($nomComplet)) ?: []));
        $nom = $parts[0] ?? '';
        $prenom = $parts[1] ?? '';

        $letter = function (string $word): string {
            if ($word === '') {
                return '';
            }
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
            $src = is_string($ascii) && $ascii !== '' ? $ascii : $word;

            return strtoupper(substr($src, 0, 1));
        };

        $initials = $letter($nom).$letter($prenom);

        return $initials !== '' ? $initials : 'XX';
    }
};
