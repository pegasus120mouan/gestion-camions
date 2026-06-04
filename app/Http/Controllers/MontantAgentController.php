<?php

namespace App\Http\Controllers;

use App\Models\PaiementAgent;
use App\Services\MontantAgentReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MontantAgentController extends Controller
{
    public function __construct(
        private MontantAgentReportingService $reporting
    ) {}

    public function index(Request $request)
    {
        $filtres = $this->reporting->filtresDepuisRequest($request);
        $options = $this->reporting->optionsFiltres();
        $agents = $this->fetchAgentsFromApi();
        $data = [];

        if ($agents === null) {
            return view('gestion_financiere.montant_agent', [
                'data' => [],
                'external_error' => 'Impossible de charger la liste des agents. Vérifiez l’API agents et la connexion réseau, puis rechargez la page.',
                'search' => trim((string) $request->query('q', '')),
                'agentNoms' => [],
                'filtres' => $filtres,
                'filtresActifs' => false,
                'produits' => $options['produits'],
                'usines' => $options['usines'],
            ]);
        }

        foreach ($agents as $agent) {
            $idAgent = (int) ($agent['id_agent'] ?? 0);
            if ($idAgent <= 0) {
                continue;
            }

            $montantDu = (int) round($this->reporting->calculerMontantDuAgent($idAgent, $filtres));
            $montantDuGlobal = (int) round($this->reporting->calculerMontantDuAgent($idAgent, ['id_agent' => $idAgent]));
            $montantPaye = (int) PaiementAgent::where('id_agent', $idAgent)->sum('montant');
            $filtresActifs = $this->reporting->filtresActifs($filtres);

            if ($filtresActifs && $montantDu === 0) {
                continue;
            }

            $data[] = [
                'agent' => $agent,
                'montant_du' => $montantDu,
                'montant_du_global' => $montantDuGlobal,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => $montantDuGlobal - $montantPaye,
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
            'filtres' => $filtres,
            'filtresActifs' => $this->reporting->filtresActifs($filtres),
            'produits' => $options['produits'],
            'usines' => $options['usines'],
        ]);
    }

    public function syntheseProduit(Request $request)
    {
        $filtres = $this->reporting->filtresDepuisRequest($request);
        $options = $this->reporting->optionsFiltres();
        $synthese = $this->reporting->syntheseParProduit($filtres);

        $totaux = [
            'montant' => (int) collect($synthese)->sum('montant_total'),
            'poids' => (float) collect($synthese)->sum('poids_total'),
            'fiches' => (int) collect($synthese)->sum('nb_fiches'),
        ];

        return view('gestion_financiere.synthese_produit', [
            'synthese' => $synthese,
            'filtres' => $filtres,
            'filtresActifs' => $this->reporting->filtresActifs($filtres),
            'produits' => $options['produits'],
            'usines' => $options['usines'],
            'totaux' => $totaux,
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

    public function show(Request $request, int $id_agent)
    {
        $agent = $this->findAgentById($id_agent);
        if (!$agent) {
            return redirect()->route('gestionfinanciere.montant_agent')
                ->withErrors(['error' => 'Agent non trouvé.']);
        }

        $filtres = $this->reporting->filtresDepuisRequest($request);
        $filtres['id_agent'] = $id_agent;

        $montantDu = (int) round($this->reporting->calculerMontantDuAgent($id_agent, $filtres));
        $montantDuGlobal = (int) round($this->reporting->calculerMontantDuAgent($id_agent, ['id_agent' => $id_agent]));
        $paiements = PaiementAgent::where('id_agent', $id_agent)
            ->orderBy('date_paiement', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        $montantPaye = (int) $paiements->sum('montant');
        $resteAPayer = $montantDuGlobal - $montantPaye;

        $fichesAvecMontant = $this->reporting->fichesAvecMontant($filtres);
        $groupesProduitUsine = $this->reporting->grouperParProduitEtUsine($fichesAvecMontant);
        $options = $this->reporting->optionsFiltres();

        return view('gestion_financiere.agent_financier_detail', [
            'agent' => $agent,
            'fichesAvecMontant' => $fichesAvecMontant,
            'groupesProduitUsine' => $groupesProduitUsine,
            'paiements' => $paiements,
            'montantDu' => $montantDu,
            'montantDuGlobal' => $montantDuGlobal,
            'montantPaye' => $montantPaye,
            'resteAPayer' => $resteAPayer,
            'filtres' => $filtres,
            'filtresActifs' => $this->reporting->filtresActifs($filtres),
            'produits' => $options['produits'],
            'usines' => $options['usines'],
            'queryFiltres' => $this->reporting->filtresPourUrl($filtres),
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
