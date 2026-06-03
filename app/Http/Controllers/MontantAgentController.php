<?php

namespace App\Http\Controllers;

use App\Models\FicheSortie;
use App\Models\PaiementAgent;
use App\Services\MontantAgentFicheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MontantAgentController extends Controller
{
    public function __construct(
        private MontantAgentFicheService $montantAgentFiche
    ) {}

    public function index(Request $request)
    {
        $agents = $this->fetchAgentsFromApi();
        $data = [];

        if ($agents === null) {
            return view('gestion_financiere.montant_agent', [
                'data' => [],
                'external_error' => 'Impossible de charger la liste des agents. Vérifiez l’API agents et la connexion réseau, puis rechargez la page.',
                'search' => trim((string) $request->query('q', '')),
                'agentNoms' => [],
            ]);
        }

        foreach ($agents as $agent) {
            $idAgent = (int) ($agent['id_agent'] ?? 0);
            if ($idAgent <= 0) {
                continue;
            }

            $montantDu = (int) round($this->calculerMontantDuAgent($idAgent));
            $montantPaye = (int) PaiementAgent::where('id_agent', $idAgent)->sum('montant');
            $resteAPayer = $montantDu - $montantPaye;

            $data[] = [
                'agent' => $agent,
                'montant_du' => $montantDu,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => $resteAPayer,
            ];
        }

        usort($data, function ($a, $b) {
            return strcasecmp(
                (string) ($a['agent']['nom_complet'] ?? ''),
                (string) ($b['agent']['nom_complet'] ?? '')
            );
        });

        $agentNoms = collect($data)
            ->map(function ($item) {
                $agent = $item['agent'];
                $nom = trim((string) ($agent['nom_complet'] ?? ''));
                if ($nom === '') {
                    $nom = trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? ''));
                }

                return $nom;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $data = array_values(array_filter($data, function ($item) use ($needle) {
                $agent = $item['agent'];
                $nomComplet = mb_strtolower(trim((string) ($agent['nom_complet'] ?? '')));
                if ($nomComplet === '') {
                    $nomComplet = mb_strtolower(trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? '')));
                }
                $numeroAgent = mb_strtolower((string) ($agent['numero_agent'] ?? ''));

                return str_contains($nomComplet, $needle) || str_contains($numeroAgent, $needle);
            }));
        }

        return view('gestion_financiere.montant_agent', [
            'data' => $data,
            'external_error' => null,
            'search' => $search,
            'agentNoms' => $agentNoms,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchAgentsFromApi(): ?array
    {
        $mesAgentsUrl = (string) config('services.external_auth.mes_agents_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $all = [];
        $page = 1;
        $maxPages = 50;

        try {
            while ($page <= $maxPages) {
                $response = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get($mesAgentsUrl, ['page' => $page]);

                if (!$response->successful()) {
                    return $page === 1 ? null : $all;
                }

                $batch = $response->json('agents') ?? [];
                if (!is_array($batch) || $batch === []) {
                    break;
                }

                foreach ($batch as $a) {
                    if (is_array($a)) {
                        $all[] = $a;
                    }
                }

                $pagination = $response->json('pagination') ?? [];
                $lastPage = (int) ($pagination['last_page'] ?? 1);
                if ($page >= $lastPage) {
                    break;
                }
                $page++;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return $all;
    }

    /**
     * Montant dû : uniquement les fiches déjà déchargées (montant figé au déchargement ou recalcul rétroactif).
     */
    private function calculerMontantDuAgent(int $idAgent): float
    {
        $total = 0.0;

        $fiches = FicheSortie::query()
            ->where('id_agent', $idAgent)
            ->whereNotNull('date_dechargement')
            ->get();

        foreach ($fiches as $fiche) {
            if ($fiche->montant_agent !== null) {
                $total += (float) $fiche->montant_agent;

                continue;
            }

            $pu = $this->montantAgentFiche->prixUnitairePourFiche($fiche);
            if ($pu !== null && (float) $fiche->poids_pont > 0) {
                $total += $pu * (float) $fiche->poids_pont;
            }
        }

        return $total;
    }

    public function show(int $id_agent)
    {
        $agent = $this->findAgentById($id_agent);
        if (!$agent) {
            return redirect()->route('gestionfinanciere.montant_agent')
                ->withErrors(['error' => 'Agent non trouvé.']);
        }

        $montantDu = (int) round($this->calculerMontantDuAgent($id_agent));
        $paiements = PaiementAgent::where('id_agent', $id_agent)
            ->orderBy('date_paiement', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        $montantPaye = (int) $paiements->sum('montant');
        $resteAPayer = $montantDu - $montantPaye;

        $fiches = FicheSortie::query()
            ->where('id_agent', $id_agent)
            ->whereNotNull('date_dechargement')
            ->orderBy('date_chargement', 'desc')
            ->get();

        $fichesAvecMontant = [];
        foreach ($fiches as $fiche) {
            $pu = $this->montantAgentFiche->prixUnitairePourFiche($fiche);
            if ($fiche->montant_agent !== null) {
                $montantLigne = (int) round((float) $fiche->montant_agent);
            } else {
                $montantLigne = $pu !== null && (float) $fiche->poids_pont > 0
                    ? (int) round($pu * (float) $fiche->poids_pont)
                    : 0;
            }
            $fichesAvecMontant[] = [
                'fiche' => $fiche,
                'montant' => $montantLigne,
                'prix_unitaire' => $pu,
            ];
        }

        return view('gestion_financiere.agent_financier_detail', [
            'agent' => $agent,
            'fichesAvecMontant' => $fichesAvecMontant,
            'paiements' => $paiements,
            'montantDu' => $montantDu,
            'montantPaye' => $montantPaye,
            'resteAPayer' => $resteAPayer,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findAgentById(int $id_agent): ?array
    {
        $mesAgentsUrl = (string) config('services.external_auth.mes_agents_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $page = 1;
        $maxPages = 50;

        try {
            while ($page <= $maxPages) {
                $response = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get($mesAgentsUrl, ['page' => $page]);

                if (!$response->successful()) {
                    break;
                }

                $agents = $response->json('agents') ?? [];
                foreach ($agents as $a) {
                    if (($a['id_agent'] ?? 0) == $id_agent) {
                        return $a;
                    }
                }

                $pagination = $response->json('pagination') ?? [];
                $lastPage = (int) ($pagination['last_page'] ?? 1);
                if ($page >= $lastPage) {
                    break;
                }
                $page++;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public function storePaiement(Request $request, int $id_agent)
    {
        if (!$this->findAgentById($id_agent)) {
            return redirect()->route('gestionfinanciere.montant_agent')
                ->withErrors(['error' => 'Agent non trouvé.']);
        }

        $validated = $request->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        PaiementAgent::create(array_merge($validated, ['id_agent' => $id_agent]));

        return back()->with('success', 'Paiement enregistré avec succès.');
    }
}
