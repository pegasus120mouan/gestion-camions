<?php

namespace App\Http\Controllers;

use App\Models\BordereauAgent;
use App\Models\PaiementAgent;
use App\Services\BordereauAgentService;
use App\Services\MesAgentsService;
use App\Services\MontantAgentReportingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MontantAgentController extends Controller
{
    public function __construct(
        private MontantAgentReportingService $reporting,
        private BordereauAgentService $bordereauAgent,
        private MesAgentsService $mesAgentsService,
    ) {}

    public function index(Request $request)
    {
        $filtres = $this->reporting->filtresDepuisRequest($request);
        $options = $this->reporting->optionsFiltres();
        $search = trim((string) $request->query('q', ''));
        $agents = $this->fetchAgentsFromApi($search !== '' ? $search : null);
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
            $montantPaye = $this->montantPayeAgent($idAgent);
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
    private function fetchAgentsFromApi(?string $search = null): ?array
    {
        $params = ['per_page' => 100];
        if ($search !== null && $search !== '') {
            $params['search'] = $search;
        }

        $agents = $this->mesAgentsService->fetchAllAgents($params);
        if ($agents === []) {
            $probe = $this->mesAgentsService->listAgents(array_merge($params, ['page' => 1]));
            if ($probe['error']) {
                return null;
            }
        }

        return $agents;
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

        $this->reporting->synchroniserTicketsAgent($id_agent, $request);

        $montantDu = (int) round($this->reporting->calculerMontantDuAgent($id_agent, $filtres));
        $montantDuGlobal = (int) round($this->reporting->calculerMontantDuAgent($id_agent, ['id_agent' => $id_agent]));
        $paiements = PaiementAgent::where('id_agent', $id_agent)
            ->with('bordereau')
            ->orderBy('date_paiement', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        $montantPaye = $this->montantPayeAgent($id_agent);
        $resteAPayer = $montantDuGlobal - $montantPaye;

        $fichesAvecMontant = $this->reporting->fichesAvecMontant($filtres);
        $ticketIdsBordereau = $this->bordereauAgent->ticketIdsDejaBorderees($id_agent);
        $ficheIdsBordereau = $this->bordereauAgent->ficheIdsDejaBorderees($id_agent);
        if ($ticketIdsBordereau !== [] || $ficheIdsBordereau !== []) {
            $fichesAvecMontant = array_values(array_filter(
                $fichesAvecMontant,
                function ($item) use ($ticketIdsBordereau, $ficheIdsBordereau) {
                    $ticketId = (int) ($item['ticket']->id_ticket ?? 0);
                    if ($ticketId > 0 && in_array($ticketId, $ticketIdsBordereau, true)) {
                        return false;
                    }
                    $ficheId = (int) ($item['fiche']->id ?? 0);
                    if ($ficheId > 0 && in_array($ficheId, $ficheIdsBordereau, true)) {
                        return false;
                    }

                    return true;
                }
            ));
        }
        $groupesProduitUsine = $this->reporting->grouperParProduitEtUsine($fichesAvecMontant);
        $options = $this->reporting->optionsFiltres();
        $bordereaux = BordereauAgent::where('id_agent', $id_agent)
            ->orderBy('date_generation', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $nomComplet = trim((string) ($agent['nom_complet'] ?? ''));
        if ($nomComplet === '') {
            $nomComplet = trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? ''));
        }

        return view('gestion_financiere.agent_financier_detail', [
            'agent' => $agent,
            'exempleNumeroBordereau' => $this->bordereauAgent->exempleNumero(
                $agent['numero_agent'] ?? null,
                $nomComplet
            ),
            'fichesAvecMontant' => $fichesAvecMontant,
            'groupesProduitUsine' => $groupesProduitUsine,
            'bordereaux' => $bordereaux,
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
        return $this->mesAgentsService->findAgentById($id_agent);
    }

    private function montantPayeAgent(int $idAgent): int
    {
        $viaBordereaux = (int) round((float) BordereauAgent::where('id_agent', $idAgent)->sum('montant_paye'));
        $avances = (int) round((float) PaiementAgent::where('id_agent', $idAgent)->whereNull('id_bordereau')->sum('montant'));

        return $viaBordereaux + $avances;
    }

    public function storeAvance(Request $request, int $id_agent)
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

        $paiement = PaiementAgent::create([
            'id_agent' => $id_agent,
            'id_bordereau' => null,
            'montant' => $validated['montant'],
            'date_paiement' => $validated['date_paiement'],
            'mode_paiement' => $validated['mode_paiement'] ?? 'Espèces',
            'reference' => $validated['reference'] ?? null,
            'commentaire' => $validated['commentaire'] ?? 'Avance',
        ]);

        app(\App\Services\RecuPaiementService::class)->assignerNumero($paiement);

        return redirect()
            ->route('gestionfinanciere.agent.show', ['id_agent' => $id_agent])
            ->with('success', 'Avance de ' . number_format($validated['montant'], 0, ',', ' ') . ' FCFA enregistrée.')
            ->with('recu_paiement_id', $paiement->id);
    }

    public function storePaiement(Request $request, int $id_agent)
    {
        return back()->withErrors([
            'error' => 'Les paiements doivent être enregistrés sur un bordereau (bouton paiement dans la section Gestion bordereaux).',
        ]);
    }

    public function storePaiementBordereau(Request $request, int $id_agent, int $id)
    {
        if (!$this->findAgentById($id_agent)) {
            return redirect()->route('gestionfinanciere.montant_agent')
                ->withErrors(['error' => 'Agent non trouvé.']);
        }

        $bordereau = BordereauAgent::where('id_agent', $id_agent)->findOrFail($id);

        $validated = $request->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        PaiementAgent::create([
            'id_agent' => $id_agent,
            'id_bordereau' => $bordereau->id,
            'montant' => $validated['montant'],
            'date_paiement' => $validated['date_paiement'],
            'mode_paiement' => $validated['mode_paiement'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'commentaire' => $validated['commentaire'] ?? null,
        ]);

        $paiement = PaiementAgent::query()
            ->where('id_agent', $id_agent)
            ->where('id_bordereau', $bordereau->id)
            ->orderByDesc('id')
            ->first();

        if ($paiement) {
            app(\App\Services\RecuPaiementService::class)->assignerNumero($paiement);
        }

        $bordereau->update([
            'montant_paye' => (float) $bordereau->montant_paye + $validated['montant'],
        ]);

        return back()->with('success', 'Paiement de ' . number_format($validated['montant'], 0, ',', ' ') . ' FCFA enregistré pour le bordereau ' . $bordereau->numero . '.');
    }

    public function fichesEligiblesBordereau(Request $request, int $id_agent)
    {
        if (!$this->findAgentById($id_agent)) {
            return response()->json(['message' => 'Agent non trouvé.'], 404);
        }

        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        $fiches = $this->bordereauAgent->fichesEligibles(
            $id_agent,
            $validated['date_debut'],
            $validated['date_fin']
        );

        return response()->json([
            'fiches' => $fiches,
            'total_montant' => (int) collect($fiches)->sum('montant'),
            'total_poids' => (float) collect($fiches)->sum('poids'),
        ]);
    }

    public function storeBordereau(Request $request, int $id_agent)
    {
        $agent = $this->findAgentById($id_agent);
        if (!$agent) {
            return redirect()->route('gestionfinanciere.montant_agent')
                ->withErrors(['error' => 'Agent non trouvé.']);
        }

        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'fiche_ids' => ['nullable', 'array'],
            'fiche_ids.*' => ['integer'],
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer'],
        ]);

        $ticketIds = $validated['ticket_ids'] ?? $validated['fiche_ids'] ?? [];
        $lignesData = $this->bordereauAgent->construireLignesData($id_agent, $ticketIds);

        if ($lignesData === []) {
            return back()->withErrors(['error' => 'Aucun ticket valide sélectionné (déjà bordereau ou introuvable).']);
        }

        $nomComplet = trim((string) ($agent['nom_complet'] ?? ''));
        if ($nomComplet === '') {
            $nomComplet = trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? ''));
        }

        $bordereau = BordereauAgent::create([
            'id_agent' => $id_agent,
            'numero' => $this->bordereauAgent->genererNumero(
                $agent['numero_agent'] ?? null,
                $nomComplet
            ),
            'agent_nom' => $nomComplet,
            'agent_numero' => $agent['numero_agent'] ?? null,
            'date_generation' => now()->toDateString(),
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'montant_total' => collect($lignesData)->sum('montant'),
            'poids_total' => collect($lignesData)->sum('poids'),
            'fiches_data' => $lignesData,
        ]);

        $this->bordereauAgent->assignerLignesAuBordereau($bordereau, $lignesData);

        return redirect()->route('gestionfinanciere.agent.bordereau.show', [
            'id_agent' => $id_agent,
            'id' => $bordereau->id,
        ])->with('success', 'Bordereau ' . $bordereau->numero . ' généré avec succès.');
    }

    public function showBordereau(int $id_agent, int $id)
    {
        $agent = $this->findAgentById($id_agent);
        if (!$agent) {
            return redirect()->route('gestionfinanciere.montant_agent')
                ->withErrors(['error' => 'Agent non trouvé.']);
        }

        $bordereau = BordereauAgent::where('id_agent', $id_agent)->findOrFail($id);
        $groupesUsine = $this->bordereauAgent->grouperParUsine($bordereau->fiches_data ?? []);

        return view('gestion_financiere.bordereau_agent_show', [
            'agent' => $agent,
            'bordereau' => $bordereau,
            'groupesUsine' => $groupesUsine,
        ]);
    }

    public function exportBordereauPdf(int $id_agent, int $id)
    {
        $agent = $this->findAgentById($id_agent);
        if (!$agent) {
            abort(404);
        }

        $bordereau = BordereauAgent::where('id_agent', $id_agent)->findOrFail($id);
        $groupesUsine = $this->bordereauAgent->grouperParUsine($bordereau->fiches_data ?? []);

        $nomComplet = trim((string) ($agent['nom_complet'] ?? ''));
        if ($nomComplet === '') {
            $nomComplet = trim(($agent['nom_agent'] ?? '') . ' ' . ($agent['prenom_agent'] ?? ''));
        }

        $logoPath = public_path('img/logo/logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = null;
        }

        $pdf = Pdf::loadView('gestion_financiere.bordereau_agent_pdf', [
            'bordereau' => $bordereau,
            'groupesUsine' => $groupesUsine,
            'agentNom' => $nomComplet,
            'agentNumero' => $agent['numero_agent'] ?? '',
            'logoPath' => $logoPath,
            'dateCreation' => ($bordereau->created_at ?? now())->format('d/m/Y \à H:i'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'bordereau_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $bordereau->numero) . '.pdf';

        return $pdf->stream($filename);
    }

    public function destroyBordereau(int $id_agent, int $id)
    {
        $bordereau = BordereauAgent::where('id_agent', $id_agent)->findOrFail($id);
        $this->bordereauAgent->libererFichesDuBordereau($bordereau);
        $bordereau->delete();

        return redirect()->route('gestionfinanciere.agent.show', ['id_agent' => $id_agent])
            ->with('success', 'Bordereau supprimé.');
    }
}
