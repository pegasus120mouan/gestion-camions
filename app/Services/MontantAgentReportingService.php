<?php

namespace App\Services;

use App\Models\FicheSortie;
use App\Models\Produit;
use App\Models\Ticket;
use App\Models\TicketValidation;
use App\Models\Usine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class MontantAgentReportingService
{
    public function __construct(
        private MontantAgentFicheService $montantAgentFiche,
        private TicketPrixService $ticketPrix,
    ) {}

    /**
     * @return array{
     *   produit_id: int|null,
     *   usine: string|null,
     *   date_debut: string|null,
     *   date_fin: string|null,
     *   id_agent: int|null
     * }
     */
    public function filtresDepuisRequest(Request $request): array
    {
        $produitId = $request->filled('produit_id') ? (int) $request->input('produit_id') : null;

        return [
            'produit_id' => $produitId > 0 ? $produitId : null,
            'usine' => $request->filled('usine') ? trim((string) $request->input('usine')) : null,
            'date_debut' => $request->filled('date_debut') ? (string) $request->input('date_debut') : null,
            'date_fin' => $request->filled('date_fin') ? (string) $request->input('date_fin') : null,
            'id_agent' => $request->filled('id_agent') ? (int) $request->input('id_agent') : null,
        ];
    }

    /**
     * Tickets validés dans gest-camions — source principale de l'espace agent-financier.
     *
     * @param  array<string, mixed>  $filtres
     */
    public function queryTicketsValidesAgent(array $filtres = []): Builder
    {
        $idAgent = ! empty($filtres['id_agent']) ? (int) $filtres['id_agent'] : 0;
        $apiRefs = $idAgent > 0 ? $this->referencesApiTicketsAgent($idAgent) : ['ids' => [], 'numeros' => []];

        $query = Ticket::query()
            ->where(function (Builder $validatedQuery) {
                $validatedQuery->whereHas('validation')
                    ->orWhere('conformite', 'valide');
            })
            ->where(function (Builder $agentQuery) use ($idAgent, $apiRefs) {
                if ($idAgent > 0) {
                    $agentQuery->where('id_agent', $idAgent)
                        ->orWhereHas('ficheSortie', fn (Builder $f) => $f->where('id_agent', $idAgent));

                    if ($apiRefs['ids'] !== []) {
                        $agentQuery->orWhereIn('id_ticket', $apiRefs['ids']);
                    }
                    if ($apiRefs['numeros'] !== []) {
                        $agentQuery->orWhereIn('numero_ticket', $apiRefs['numeros']);
                    }
                } else {
                    $agentQuery->where('id_agent', '>', 0);
                }
            });

        if (! empty($filtres['date_debut'])) {
            $query->whereDate('date_ticket', '>=', $filtres['date_debut']);
        }

        if (! empty($filtres['date_fin'])) {
            $query->whereDate('date_ticket', '<=', $filtres['date_fin']);
        }

        if (! empty($filtres['sans_bordereau'])) {
            $query->whereNull('bordereau_agent_id');
        }

        if (! empty($filtres['usine'])) {
            $nomUsine = trim((string) $filtres['usine']);
            $idsUsine = Usine::query()
                ->where('nom_usine', $nomUsine)
                ->pluck('id_usine');

            $query->where(function (Builder $sub) use ($nomUsine, $idsUsine) {
                if ($idsUsine->isNotEmpty()) {
                    $sub->whereIn('id_usine', $idsUsine);
                }
                $sub->orWhereHas('ficheSortie', fn (Builder $f) => $f->where('usine', $nomUsine));
            });
        }

        if (! empty($filtres['produit_id'])) {
            $produitId = (int) $filtres['produit_id'];
            $query->where(function (Builder $sub) use ($produitId) {
                $sub->whereHas('ficheSortie', fn (Builder $f) => $f->where('produit_id', $produitId))
                    ->orWhereDoesntHave('ficheSortie');
            });
        }

        return $query;
    }

    /**
     * Rattache id_agent, validations manquantes et fiches locales pour les tickets API de l'agent.
     */
    public function synchroniserTicketsAgent(int $idAgent, ?Request $request = null): int
    {
        if ($idAgent <= 0) {
            return 0;
        }

        $request ??= request();
        $apiTickets = $this->apiTicketsPourAgent($idAgent, $request);

        if ($apiTickets === []) {
            return 0;
        }

        $count = 0;

        foreach ($apiTickets as $apiTicket) {
            $idTicket = (int) ($apiTicket['id_ticket'] ?? 0);
            if ($idTicket <= 0) {
                continue;
            }

            $numero = trim((string) ($apiTicket['numero_ticket'] ?? ''));
            $estValideGest = TicketValidation::query()
                ->where(function (Builder $q) use ($idTicket, $numero) {
                    $q->where('id_ticket', $idTicket);
                    if ($numero !== '') {
                        $q->orWhere('numero_ticket', $numero);
                    }
                })
                ->exists();

            $ticketLocal = Ticket::query()->find($idTicket);
            $legacyValide = $ticketLocal && $ticketLocal->conformite === 'valide';

            if (! $estValideGest && ! $legacyValide) {
                continue;
            }

            $ticket = $this->assurerTicketDepuisApi($apiTicket, $idAgent);

            if ($legacyValide && ! $estValideGest) {
                TicketValidation::updateOrCreate(
                    ['id_ticket' => $idTicket],
                    [
                        'numero_ticket' => $numero !== '' ? $numero : (string) $idTicket,
                        'validated_at' => $ticketLocal->updated_at ?? now(),
                        'validated_by' => $ticketLocal->id_utilisateur,
                    ]
                );
            }

            if ((int) ($ticket->id_agent ?? 0) !== $idAgent) {
                $ticket->update(['id_agent' => $idAgent]);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Crée ou met à jour la fiche locale gest-camions à partir d'un ticket API.
     *
     * @param  array<string, mixed>  $apiTicket
     */
    public function assurerTicketDepuisApi(array $apiTicket, int $idAgent): Ticket
    {
        $idTicket = (int) ($apiTicket['id_ticket'] ?? 0);
        if ($idTicket <= 0) {
            throw new \InvalidArgumentException('Ticket API sans identifiant.');
        }

        $attrs = [
            'numero_ticket' => (string) ($apiTicket['numero_ticket'] ?? ''),
            'date_ticket' => $apiTicket['date_ticket'] ?? now()->format('Y-m-d'),
            'matricule_vehicule' => (string) ($apiTicket['matricule_vehicule'] ?? ''),
            'vehicule_id' => (int) ($apiTicket['vehicule_id'] ?? 0) ?: null,
            'poids' => $apiTicket['poids'] ?? null,
            'id_usine' => (int) ($apiTicket['id_usine'] ?? 0) ?: null,
            'id_agent' => $idAgent,
            'statut_ticket' => $apiTicket['statut_ticket'] ?? 'non soldé',
            'prix_unitaire' => $apiTicket['prix_unitaire'] ?? null,
            'montant_paie' => $apiTicket['montant_paie'] ?? null,
        ];

        $ticket = Ticket::query()->find($idTicket);
        if ($ticket) {
            $ticket->fill($attrs);
            $ticket->save();

            return $ticket->fresh();
        }

        $ticket = new Ticket($attrs);
        $ticket->id_ticket = $idTicket;
        $ticket->id_utilisateur = Auth::id() ?? 1;
        $ticket->save();

        return $ticket->fresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function apiTicketsPourAgent(int $idAgent, ?Request $request = null): array
    {
        $request ??= request();

        return array_values(array_filter(
            app(MesTicketsService::class)->fetchAllTickets([], $request),
            static fn (array $ticket): bool => (int) ($ticket['id_agent'] ?? 0) === $idAgent
        ));
    }

    /**
     * @return array{ids: list<int>, numeros: list<string>}
     */
    private function referencesApiTicketsAgent(int $idAgent, ?Request $request = null): array
    {
        $ids = [];
        $numeros = [];

        foreach ($this->apiTicketsPourAgent($idAgent, $request) as $apiTicket) {
            $idTicket = (int) ($apiTicket['id_ticket'] ?? 0);
            if ($idTicket > 0) {
                $ids[] = $idTicket;
            }

            $numero = trim((string) ($apiTicket['numero_ticket'] ?? ''));
            if ($numero !== '') {
                $numeros[] = $numero;
            }
        }

        return [
            'ids' => array_values(array_unique($ids)),
            'numeros' => array_values(array_unique($numeros)),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtres
     * @deprecated Conservé pour compatibilité interne ; préférer queryTicketsValidesAgent.
     */
    public function queryFichesDechargees(array $filtres = []): Builder
    {
        $query = FicheSortie::query()
            ->whereNotNull('date_dechargement')
            ->whereNotNull('id_agent');

        if (!empty($filtres['id_agent'])) {
            $query->where('id_agent', (int) $filtres['id_agent']);
        }

        if (!empty($filtres['produit_id'])) {
            $query->where('produit_id', (int) $filtres['produit_id']);
        }

        if (!empty($filtres['usine'])) {
            $query->where('usine', $filtres['usine']);
        }

        if (!empty($filtres['date_debut'])) {
            $query->whereDate('date_dechargement', '>=', $filtres['date_debut']);
        }

        if (!empty($filtres['date_fin'])) {
            $query->whereDate('date_dechargement', '<=', $filtres['date_fin']);
        }

        if (!empty($filtres['sans_bordereau'])) {
            $query->whereNull('bordereau_agent_id');
        }

        return $query;
    }

    public function montantLigneFiche(FicheSortie $fiche): int
    {
        if ($fiche->montant_agent !== null) {
            return (int) round((float) $fiche->montant_agent);
        }

        $pu = $this->montantAgentFiche->prixUnitairePourFiche($fiche);
        $poids = (float) $fiche->poids_pont;

        if ($poids <= 0 && $fiche->id_ticket) {
            $ticket = Ticket::where('id_ticket', $fiche->id_ticket)->first();
            $poids = $ticket ? (float) ($ticket->poids ?? 0) : 0;
        }

        return $pu !== null && $poids > 0
            ? (int) round($pu * $poids)
            : 0;
    }

    public function montantLigneTicket(Ticket $ticket, ?FicheSortie $fiche = null, ?int $produitIdOverride = null): int
    {
        if ($ticket->montant_paie !== null && (float) $ticket->montant_paie > 0) {
            return (int) round((float) $ticket->montant_paie);
        }

        if ($fiche && $fiche->exists && $fiche->montant_agent !== null) {
            return (int) round((float) $fiche->montant_agent);
        }

        $usinesById = $this->buildUsinesById();
        $nomUsine = $this->nomUsineEffectif($fiche, $ticket, $usinesById);
        $produitId = $produitIdOverride
            ?: ($fiche?->produit_id ? (int) $fiche->produit_id : null);

        $pu = (float) ($ticket->prix_unitaire ?? 0) > 0
            ? (float) $ticket->prix_unitaire
            : $this->ticketPrix->prixUnitairePourTicket(
                $ticket,
                $produitId,
                null,
                null,
                (int) ($ticket->id_agent ?? 0) ?: null,
                $nomUsine,
            );

        $poids = (float) ($ticket->poids ?? 0);
        if ($poids <= 0 && $fiche) {
            $poids = (float) ($fiche->poids_pont ?? 0);
        }

        return $pu !== null && $poids > 0 ? (int) round($pu * $poids) : 0;
    }

    public function prixUnitaireLigneTicket(Ticket $ticket, ?FicheSortie $fiche = null, ?int $produitIdOverride = null): ?float
    {
        if ((float) ($ticket->prix_unitaire ?? 0) > 0) {
            return (float) $ticket->prix_unitaire;
        }

        if ($fiche && $fiche->exists) {
            $puFiche = $this->montantAgentFiche->prixUnitairePourFiche($fiche);
            if ($puFiche !== null) {
                return $puFiche;
            }
        }

        $usinesById = $this->buildUsinesById();
        $nomUsine = $this->nomUsineEffectif($fiche, $ticket, $usinesById);
        $produitId = $produitIdOverride
            ?: ($fiche?->produit_id ? (int) $fiche->produit_id : null);

        return $this->ticketPrix->prixUnitairePourTicket(
            $ticket,
            $produitId,
            null,
            null,
            (int) ($ticket->id_agent ?? 0) ?: null,
            $nomUsine,
        );
    }

    /**
     * @param  array<string, mixed>  $filtres
     */
    public function calculerMontantDuAgent(int $idAgent, array $filtres = []): float
    {
        $filtres['id_agent'] = $idAgent;
        $total = 0.0;

        foreach ($this->lignesAvecMontant($filtres) as $item) {
            $total += (int) ($item['montant'] ?? 0);
        }

        return $total;
    }

    /**
     * Lignes agent : ticket validé (+ fiche PGF optionnelle).
     *
     * @param  array<string, mixed>  $filtres
     * @return list<array{
     *   ticket: Ticket,
     *   fiche: FicheSortie,
     *   a_fiche: bool,
     *   montant: int,
     *   prix_unitaire: float|null,
     *   poids_effectif: float
     * }>
     */
    public function lignesAvecMontant(array $filtres = []): array
    {
        if (! empty($filtres['id_agent'])) {
            $this->synchroniserTicketsAgent((int) $filtres['id_agent']);
        }

        $tickets = $this->queryTicketsValidesAgent($filtres)
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket')
            ->get();

        if ($tickets->isEmpty()) {
            return [];
        }

        $ticketIds = $tickets->pluck('id_ticket')->all();
        $numerosTickets = $tickets
            ->pluck('numero_ticket')
            ->map(fn ($numero) => trim((string) $numero))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $fichesCandidates = FicheSortie::query()
            ->where(function ($query) use ($ticketIds, $numerosTickets) {
                if ($ticketIds !== []) {
                    $query->whereIn('id_ticket', $ticketIds);
                }
                if ($numerosTickets !== []) {
                    $method = $ticketIds !== [] ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('numero_ticket', $numerosTickets);
                }
            })
            ->orderByDesc('id')
            ->get();

        $fichesByTicketId = $fichesCandidates->keyBy('id_ticket');
        $fichesByNumero = $fichesCandidates
            ->filter(fn (FicheSortie $fiche) => trim((string) ($fiche->numero_ticket ?? '')) !== '')
            ->keyBy(fn (FicheSortie $fiche) => mb_strtolower(trim((string) $fiche->numero_ticket)));

        $usinesById = $this->buildUsinesById();
        $produitIdFiltre = ! empty($filtres['produit_id']) ? (int) $filtres['produit_id'] : null;
        $result = [];

        foreach ($tickets as $ticket) {
            if ($ticket->bordereau_agent_id) {
                continue;
            }

            $fiche = $fichesByTicketId->get($ticket->id_ticket);
            if (! $fiche) {
                $numero = trim((string) ($ticket->numero_ticket ?? ''));
                if ($numero !== '') {
                    $fiche = $fichesByNumero->get(mb_strtolower($numero));
                }
            }

            $aFiche = false;
            if ($fiche && $this->montantAgentFiche->ficheCorrespondAuTicket($fiche, $ticket)) {
                $this->montantAgentFiche->reconcilierFicheAvecTicket($fiche, $ticket);
                $aFiche = true;
            } else {
                $fiche = null;
            }

            if ($produitIdFiltre && $fiche && $fiche->produit_id && (int) $fiche->produit_id !== $produitIdFiltre) {
                continue;
            }

            if ($produitIdFiltre && ! $fiche) {
                continue;
            }

            $nomUsine = $this->nomUsineEffectif($fiche, $ticket, $usinesById);
            $produitId = $fiche?->produit_id ?: $produitIdFiltre;
            $poids = (float) ($ticket->poids ?? 0);
            if ($poids <= 0 && $fiche) {
                $poids = (float) ($fiche->poids_pont ?? 0);
            }

            if ($fiche !== null) {
                $this->appliquerUsineSurFiche($fiche, $nomUsine);
            }

            $result[] = [
                'ticket' => $ticket,
                'fiche' => $fiche ?? $this->ficheVirtuelleDepuisTicket($ticket, $nomUsine, $produitId),
                'a_fiche' => $fiche !== null,
                'montant' => $this->montantLigneTicket($ticket, $fiche, $produitId),
                'prix_unitaire' => $this->prixUnitaireLigneTicket($ticket, $fiche, $produitId),
                'poids_effectif' => $poids,
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filtres
     * @return list<array{fiche: FicheSortie, montant: int, prix_unitaire: float|null, poids_effectif: float, ticket?: Ticket, a_fiche?: bool}>
     */
    public function fichesAvecMontant(array $filtres = []): array
    {
        $lignes = $this->lignesAvecMontant($filtres);

        return array_map(function (array $item) {
            return [
                'ticket' => $item['ticket'],
                'fiche' => $item['fiche'],
                'a_fiche' => $item['a_fiche'],
                'montant' => $item['montant'],
                'prix_unitaire' => $item['prix_unitaire'],
                'poids_effectif' => $item['poids_effectif'],
            ];
        }, $lignes);
    }

    private function ficheVirtuelleDepuisTicket(Ticket $ticket, ?string $nomUsine, ?int $produitId): FicheSortie
    {
        $fiche = new FicheSortie([
            'numero_fiche' => '—',
            'matricule_vehicule' => (string) ($ticket->matricule_vehicule ?? ''),
            'vehicule_id' => (int) ($ticket->vehicule_id ?? 0),
            'id_ticket' => (int) $ticket->id_ticket,
            'numero_ticket' => (string) ($ticket->numero_ticket ?? ''),
            'id_agent' => (int) ($ticket->id_agent ?? 0),
            'usine' => $nomUsine,
            'produit_id' => $produitId,
            'nom_produit' => $produitId ? (Produit::find($produitId)?->nom) : null,
            'date_chargement' => $ticket->date_ticket,
            'date_dechargement' => $ticket->date_ticket,
            'poids_pont' => $ticket->poids,
        ]);
        $fiche->id = 0;

        return $fiche;
    }

    public function nomUsinePourTicket(?FicheSortie $fiche, Ticket $ticket): ?string
    {
        return $this->nomUsineEffectif($fiche, $ticket, $this->buildUsinesById());
    }

    /**
     * @param  array<string, string>  $usinesById
     */
    private function nomUsineEffectif(?FicheSortie $fiche, Ticket $ticket, array $usinesById): ?string
    {
        $fromFiche = trim((string) ($fiche?->usine ?? ''));
        if ($fromFiche !== '') {
            return $fromFiche;
        }

        $fromTicket = $usinesById[(string) ($ticket->id_usine ?? '')] ?? null;

        return $fromTicket ? trim((string) $fromTicket) : null;
    }

    private function appliquerUsineSurFiche(FicheSortie $fiche, ?string $nomUsine): void
    {
        if ($nomUsine === null || $nomUsine === '') {
            return;
        }

        if (trim((string) ($fiche->usine ?? '')) !== '') {
            return;
        }

        $fiche->usine = $nomUsine;

        if ($fiche->exists && (int) $fiche->id > 0) {
            FicheSortie::query()
                ->whereKey($fiche->id)
                ->where(function ($query) {
                    $query->whereNull('usine')->orWhere('usine', '');
                })
                ->update(['usine' => $nomUsine]);
        }
    }

    private function buildUsinesById(): array
    {
        $index = [];
        foreach (Usine::all() as $ul) {
            $index[(string) $ul->id_usine] = $ul->nom_usine;
        }
        try {
            $url = (string) config('services.external_auth.mes_usines_url');
            $timeout = (int) config('services.external_auth.timeout', 10);
            $resp = Http::acceptJson()->withoutVerifying()->timeout($timeout)->get($url);
            if ($resp->successful()) {
                foreach ($resp->json('usines') ?? [] as $u) {
                    $key = (string) ($u['id_usine'] ?? '');
                    if ($key !== '' && !isset($index[$key])) {
                        $index[$key] = $u['nom_usine'] ?? '';
                    }
                }
            }
        } catch (\Throwable $e) {}

        return $index;
    }

    /**
     * @param  list<array{fiche: FicheSortie, montant: int, prix_unitaire: float|null}>  $fichesAvecMontant
     * @return list<array{
     *   produit: string,
     *   produit_id: int|null,
     *   montant_total: int,
     *   poids_total: float,
     *   nb_fiches: int,
     *   usines: list<array{
     *     usine: string,
     *     montant_total: int,
     *     poids_total: float,
     *     nb_fiches: int,
     *     lignes: list<array{fiche: FicheSortie, montant: int, prix_unitaire: float|null}>
     *   }>
     * }>
     */
    public function grouperParProduitEtUsine(array $fichesAvecMontant): array
    {
        $parProduit = collect($fichesAvecMontant)->groupBy(function ($item) {
            $fiche = $item['fiche'];

            return $fiche->nom_produit ?: 'Sans produit';
        });

        $groupes = [];
        foreach ($parProduit as $nomProduit => $itemsProduit) {
            $produitId = $itemsProduit->first()['fiche']->produit_id ?? null;
            $parUsine = $itemsProduit->groupBy(fn ($item) => $item['fiche']->usine ?: 'Sans usine');
            $usines = [];

            foreach ($parUsine as $nomUsine => $lignes) {
                $lignesArr = $lignes->values()->all();
                $usines[] = [
                    'usine' => $nomUsine,
                    'montant_total' => (int) $lignes->sum('montant'),
                    'poids_total' => (float) $lignes->sum(fn ($i) => (float) ($i['poids_effectif'] ?? $i['fiche']->poids_pont ?? 0)),
                    'nb_fiches' => count($lignesArr),
                    'lignes' => $lignesArr,
                ];
            }

            usort($usines, fn ($a, $b) => strcasecmp($a['usine'], $b['usine']));

            $groupes[] = [
                'produit' => $nomProduit,
                'produit_id' => $produitId,
                'montant_total' => (int) $itemsProduit->sum('montant'),
                'poids_total' => (float) $itemsProduit->sum(fn ($i) => (float) ($i['poids_effectif'] ?? $i['fiche']->poids_pont ?? 0)),
                'nb_fiches' => $itemsProduit->count(),
                'usines' => $usines,
            ];
        }

        usort($groupes, fn ($a, $b) => strcasecmp($a['produit'], $b['produit']));

        return $groupes;
    }

    /**
     * Synthèse par produit (tous agents).
     *
     * @param  array<string, mixed>  $filtres
     * @return list<array{
     *   produit: string,
     *   produit_id: int|null,
     *   montant_total: int,
     *   poids_total: float,
     *   nb_fiches: int,
     *   nb_agents: int,
     *   usines: list<array{usine: string, montant_total: int, poids_total: float, nb_fiches: int}>
     * }>
     */
    public function syntheseParProduit(array $filtres = []): array
    {
        $fichesAvecMontant = $this->fichesAvecMontant($filtres);
        $groupes = $this->grouperParProduitEtUsine($fichesAvecMontant);

        return array_map(function ($groupe) {
            $agentsIds = collect($groupe['usines'])
                ->flatMap(fn ($u) => collect($u['lignes'])->pluck('fiche.id_agent'))
                ->unique()
                ->filter();

            $groupe['nb_agents'] = $agentsIds->count();
            $groupe['usines'] = array_map(function ($usine) {
                unset($usine['lignes']);

                return $usine;
            }, $groupe['usines']);

            return $groupe;
        }, $groupes);
    }

    /**
     * @return array{produits: \Illuminate\Database\Eloquent\Collection, usines: list<string>}
     */
    public function optionsFiltres(): array
    {
        $produits = Produit::orderBy('nom')->get();

        $usinesFiches = FicheSortie::query()
            ->whereNotNull('usine')
            ->where('usine', '!=', '')
            ->distinct()
            ->pluck('usine')
            ->all();

        $usinesTickets = Usine::query()->orderBy('nom_usine')->pluck('nom_usine')->all();
        $usines = array_values(array_unique(array_merge($usinesTickets, $usinesFiches)));
        sort($usines);

        return [
            'produits' => $produits,
            'usines' => $usines,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtres
     */
    public function filtresPourUrl(array $filtres): array
    {
        return array_filter([
            'produit_id' => $filtres['produit_id'] ?? null,
            'usine' => $filtres['usine'] ?? null,
            'date_debut' => $filtres['date_debut'] ?? null,
            'date_fin' => $filtres['date_fin'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function filtresActifs(array $filtres): bool
    {
        return ($filtres['produit_id'] ?? null)
            || ($filtres['usine'] ?? null)
            || ($filtres['date_debut'] ?? null)
            || ($filtres['date_fin'] ?? null);
    }
}
