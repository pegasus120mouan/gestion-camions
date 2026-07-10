<?php

namespace App\Http\Controllers;

use App\Models\BordereauAgent;
use App\Services\CaisseService;
use App\Services\FinancementService;
use App\Services\MesAgentsService;
use Illuminate\Http\Request;

class EffectuerPaiementController extends Controller
{
    public function __construct(
        private readonly MesAgentsService $mesAgentsService,
        private readonly FinancementService $financementService,
        private readonly CaisseService $caisseService,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'statut' => trim((string) $request->query('statut', 'a_payer')),
        ];

        $agentIds = [];
        try {
            $agentIds = $this->mesAgentsService->chefAgentIds($request);
        } catch (\Throwable) {
            $agentIds = [];
        }

        $query = BordereauAgent::query()
            ->orderByDesc('date_generation')
            ->orderByDesc('id');

        if ($agentIds !== []) {
            $query->whereIn('id_agent', $agentIds);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('numero', 'like', "%{$q}%")
                    ->orWhere('agent_nom', 'like', "%{$q}%")
                    ->orWhere('agent_numero', 'like', "%{$q}%");
            });
        }

        if ($filters['statut'] === 'a_payer') {
            $query->whereRaw('COALESCE(montant_total, 0) > COALESCE(montant_paye, 0)');
        } elseif ($filters['statut'] === 'soldes') {
            $query->whereRaw('COALESCE(montant_total, 0) <= COALESCE(montant_paye, 0)');
        }

        $bordereaux = $query->paginate(25)->withQueryString();

        $financements = [];
        $agentIdsOnPage = $bordereaux->getCollection()
            ->pluck('id_agent')
            ->unique()
            ->filter()
            ->values();

        foreach ($agentIdsOnPage as $idAgent) {
            $financements[(int) $idAgent] = $this->financementService->soldeFinancementAgent((int) $idAgent);
        }

        $statsQuery = BordereauAgent::query();
        if ($agentIds !== []) {
            $statsQuery->whereIn('id_agent', $agentIds);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'a_payer' => (clone $statsQuery)
                ->whereRaw('COALESCE(montant_total, 0) > COALESCE(montant_paye, 0)')
                ->count(),
            'reste_total' => (float) (clone $statsQuery)
                ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(montant_total, 0) - COALESCE(montant_paye, 0), 0)), 0) as reste')
                ->value('reste'),
        ];

        return view('effectuer_paiement.index', [
            'bordereaux' => $bordereaux,
            'filters' => $filters,
            'financements' => $financements,
            'stats' => $stats,
            'soldeCaisseLocale' => (int) round($this->caisseService->getSolde()),
        ]);
    }
}
