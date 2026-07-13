<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\GestionFinanciereController;
use App\Http\Controllers\PontPesageController;
use App\Http\Controllers\PeseeController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\PontController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\CommisController;
use App\Http\Controllers\FinancementController;
use App\Http\Controllers\UsineController;
use App\Http\Controllers\CodeTransporteurController;
use App\Http\Controllers\StockPgfController;
use App\Http\Controllers\GroupeController;
use App\Http\Controllers\PlanteurController;
use App\Http\Controllers\MinioProxyController;
use App\Http\Controllers\ChefChargeurController;
use App\Http\Controllers\ChargeurController;
use App\Http\Controllers\ChauffeurController;
use App\Http\Controllers\TransporteurController;
use App\Http\Controllers\MontantChefChargeurController;
use App\Http\Controllers\MontantFournisseurController;
use App\Http\Controllers\MontantTransporteurController;
use App\Http\Controllers\MontantAgentController;
use App\Http\Controllers\RecuPaiementController;
use App\Http\Controllers\ChauffeurSalaireController;
use App\Http\Controllers\MontantParticulierController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\ParcController;
use App\Http\Controllers\BilanVehiculeController;
use App\Http\Controllers\PisteurController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApprovisionnementController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\SoldeChefEquipeController;
use App\Http\Controllers\EffectuerPaiementController;
use App\Http\Controllers\ParticulierController;
use App\Http\Controllers\ParticulierPrixController;
use App\Services\ChefEquipeContext;
use App\Services\SoldeChefEquipeService;
use Illuminate\Support\Facades\Route;

Route::get('/index.html', function () {
    return redirect('/');
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Proxy pour les images MinIO (bucket planteurs)
Route::get('/minio/planteurs/{filename}', [MinioProxyController::class, 'planteurImage'])->name('minio.planteur.image');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $authUser = auth()->user();
        $nombreCamions = 0;
        $totalDepenses = 0;
        $nombreTickets = 0;

        if ($authUser && $authUser->role === 'proprietaire') {
            $phpsessid = session('external_auth.phpsessid', '');
            if ($phpsessid) {
                try {
                    $response = \Illuminate\Support\Facades\Http::acceptJson()
                        ->timeout(10)
                        ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                        ->get(config('services.external_auth.mes_camions_url'));
                    if ($response->successful()) {
                        $vehicules = $response->json('vehicules');
                        $nombreCamions = is_array($vehicules) ? count($vehicules) : 0;

                        $vehiculeIds = array_column($vehicules, 'vehicules_id');
                        $totalDepenses = \App\Models\Depense::whereIn('vehicule_id', $vehiculeIds)->sum('montant');
                    }

                    $ticketsResponse = \Illuminate\Support\Facades\Http::acceptJson()
                        ->timeout(10)
                        ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                        ->get(config('services.external_auth.mes_tickets_url'));
                    if ($ticketsResponse->successful()) {
                        $pagination = $ticketsResponse->json('pagination');
                        $nombreTickets = is_array($pagination) ? ($pagination['total'] ?? 0) : 0;
                    }
                } catch (\Throwable $e) {
                    $nombreCamions = 0;
                }
            }
        }

        // Nombre de fiches de sortie non déchargées
        $fichesNonDechargees = \App\Models\FicheSortie::whereNull('date_dechargement')->count();
        $fichesDechargees = \App\Models\FicheSortie::whereNotNull('date_dechargement')->count();
        $totalFiches = $fichesNonDechargees + $fichesDechargees;

        // Dernières fiches de sortie
        $dernieresFiches = \App\Models\FicheSortie::orderBy('created_at', 'desc')->take(6)->get();

        // Dernières transactions (dépenses)
        $dernieresDepenses = \App\Models\Depense::orderBy('created_at', 'desc')->take(6)->get();

        // Statistiques de stock par pont
        $stocksParPont = [];
        $totalStockEntrees = 0;
        $totalStockSorties = 0;
        $totalStockDisponible = 0;

        try {
            $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
            $pontsResponse = \Illuminate\Support\Facades\Http::acceptJson()
                ->withoutVerifying()
                ->timeout(10)
                ->get($mesPontsUrl);
            
            if ($pontsResponse->successful()) {
                $ponts = $pontsResponse->json('ponts') ?? [];
                
                foreach ($ponts as $pont) {
                    $idPont = $pont['id_pont'] ?? 0;
                    $nomPont = $pont['nom_pont'] ?? 'Inconnu';
                    
                    // Entrées manuelles
                    $entrees = \App\Models\Stock::where('id_pont', $idPont)->where('type', 'entree')->sum('quantite');
                    // Sorties manuelles
                    $sortiesManuelles = \App\Models\Stock::where('id_pont', $idPont)->where('type', 'sortie')->sum('quantite');
                    // Sorties fiches déchargées
                    $sortiesFiches = \App\Models\FicheSortie::where('id_pont', $idPont)
                        ->whereNotNull('date_dechargement')
                        ->whereNotNull('poids_pont')
                        ->sum('poids_pont');
                    
                    $sorties = $sortiesManuelles + $sortiesFiches;
                    $disponible = max(0, $entrees - $sorties);
                    
                    if ($entrees > 0) {
                        $stocksParPont[] = [
                            'nom_pont' => $nomPont,
                            'entrees' => $entrees,
                            'sorties' => $sorties,
                            'disponible' => $disponible,
                        ];
                        
                        $totalStockEntrees += $entrees;
                        $totalStockSorties += $sorties;
                        $totalStockDisponible += $disponible;
                    }
                }
            }
        } catch (\Throwable $e) {}

        $soldeChef = null;
        $soldeChefError = null;
        $chefContext = app(ChefEquipeContext::class);
        $chefToken = $chefContext->resolveToken(request());
        if ($chefToken !== '') {
            $soldeChef = app(SoldeChefEquipeService::class)->getSoldeByToken($chefToken);
            if (!$soldeChef) {
                $soldeChefError = 'Impossible de charger le solde pour ce chef d\'équipe.';
            }
        }

        $nombreTicketsEnAttente = 0;
        try {
            $nombreTicketsEnAttente = app(\App\Services\MesTicketsService::class)
                ->countTicketsEnAttente(request());
        } catch (\Throwable $e) {
            $nombreTicketsEnAttente = 0;
        }

        $agentsCounts = ['total' => 0, 'particuliers' => 0, 'professionnels' => 0];
        try {
            $agentsCounts = app(\App\Services\MesAgentsService::class)->countBySousGroupe(request());
        } catch (\Throwable $e) {
            $agentsCounts = ['total' => 0, 'particuliers' => 0, 'professionnels' => 0];
        }

        return view('dashboard', [
            'soldeChef' => $soldeChef,
            'soldeChefError' => $soldeChefError,
            'nombreCamions' => $nombreCamions,
            'totalDepenses' => $totalDepenses,
            'nombreTickets' => $nombreTickets,
            'nombreTicketsEnAttente' => $nombreTicketsEnAttente,
            'agentsCounts' => $agentsCounts,
            'stocksParPont' => $stocksParPont,
            'totalStockEntrees' => $totalStockEntrees,
            'totalStockSorties' => $totalStockSorties,
            'totalStockDisponible' => $totalStockDisponible,
            'fichesNonDechargees' => $fichesNonDechargees,
            'fichesDechargees' => $fichesDechargees,
            'totalFiches' => $totalFiches,
            'dernieresFiches' => $dernieresFiches,
            'dernieresDepenses' => $dernieresDepenses,
        ]);
    })->name('dashboard');

    Route::get('/solde-chef-equipe', [SoldeChefEquipeController::class, 'index'])->name('solde_chef_equipe.index');
    Route::post('/solde-chef-equipe/token', [SoldeChefEquipeController::class, 'updateToken'])->name('solde_chef_equipe.token');
    Route::get('/api/solde-chef-equipe', [SoldeChefEquipeController::class, 'show'])->name('api.solde_chef_equipe');

    Route::get('/effectuer-paiement', [EffectuerPaiementController::class, 'index'])->name('effectuer_paiement.index');

    Route::get('/caisse', [CaisseController::class, 'index'])->name('caisse.index');
    Route::post('/caisse/approvisionnement', [CaisseController::class, 'storeApprovisionnement'])->name('caisse.approvisionnement.store');
    Route::get('/api/mes-agents', [AgentController::class, 'apiIndex'])->name('api.mes_agents');

    Route::get('/utilisateurs/admins', [UtilisateurController::class, 'admins'])->name('utilisateurs.admins');
    Route::get('/utilisateurs/agents', [UtilisateurController::class, 'agents'])->name('utilisateurs.agents');
    Route::get('/utilisateurs/chauffeurs', [UtilisateurController::class, 'chauffeurs'])->name('utilisateurs.chauffeurs');

    Route::resource('camions', CamionController::class)->except(['create']);
    Route::post('/vehicules/{vehicule_id}/etat', [CamionController::class, 'updateVehiculeEtat'])->name('vehicules.etat.update');
    Route::get('/camions-pgf', [CamionController::class, 'camionsPgf'])->name('camions.camions_pgf');
    Route::get('/camions-pgf/ajouter', [CamionController::class, 'ajouterCamionsPgf'])->name('camions.camions_pgf.ajouter');
    Route::post('/camions/assigner-groupe', [CamionController::class, 'assignerGroupe'])->name('camions.assigner_groupe');
    Route::post('/camions/assigner-groupe-bulk', [CamionController::class, 'assignerGroupeBulk'])->name('camions.assigner_groupe_bulk');
    Route::delete('/camions/{vehicule_id}/retirer-groupe', [CamionController::class, 'retirerGroupe'])->name('camions.retirer_groupe');
    Route::resource('ponts_pesage', PontPesageController::class)->except(['create']);
    Route::post('/ponts_pesage/{pontPesage}/toggle-gerable', [PontPesageController::class, 'toggleGerable'])->name('ponts_pesage.toggle_gerable');
    Route::get('pesees/{pesee}/ticket', [PeseeController::class, 'ticket'])->name('pesees.ticket');
    Route::post('pesees/{pesee}/validate', [PeseeController::class, 'validateStatus'])->name('pesees.validate');
    Route::post('pesees/{pesee}/cancel', [PeseeController::class, 'cancel'])->name('pesees.cancel');
    Route::resource('pesees', PeseeController::class)->except(['create']);
    Route::resource('produits', ProduitController::class)->except(['create']);
    Route::post('/produits/{produit}/usines', [ProduitController::class, 'storeUsine'])->name('produits.usines.store');
    Route::post('/usines/{code_usine}/gerable', [UsineController::class, 'toggleGerable'])->name('usines.gerable');

    Route::get('/gestionfinanciere', [GestionFinanciereController::class, 'index'])->name('gestionfinanciere.index');
    Route::post('/gestionfinanciere', [GestionFinanciereController::class, 'store'])->name('gestionfinanciere.store');
    Route::delete('/gestionfinanciere/{mouvement}', [GestionFinanciereController::class, 'destroy'])->name('gestionfinanciere.destroy');

    Route::get('/gestionfinanciere/sorties', [GestionFinanciereController::class, 'sorties'])->name('gestionfinanciere.sorties');
    Route::post('/gestionfinanciere/sorties', [GestionFinanciereController::class, 'storeSortie'])->name('gestionfinanciere.sorties.store');
    Route::get('/gestion-financiere/recus-paiement', [RecuPaiementController::class, 'index'])->name('gestionfinanciere.recus.index');
    Route::get('/gestion-financiere/recus-paiement/{id}/pdf', [RecuPaiementController::class, 'pdf'])->name('gestionfinanciere.recus.pdf');

    Route::get('/gestion-financiere/salaires-chauffeurs', [ChauffeurSalaireController::class, 'index'])->name('gestionfinanciere.chauffeurs_salaires.index');
    Route::get('/gestion-financiere/salaires-chauffeurs/{chauffeur}', [ChauffeurSalaireController::class, 'show'])->name('gestionfinanciere.chauffeurs_salaires.show');
    Route::post('/gestion-financiere/salaires-chauffeurs/{chauffeur}/avances', [ChauffeurSalaireController::class, 'storeAvance'])->name('gestionfinanciere.chauffeurs_salaires.avance.store');
    Route::post('/gestion-financiere/salaires-chauffeurs/{chauffeur}/paiements', [ChauffeurSalaireController::class, 'storePaiement'])->name('gestionfinanciere.chauffeurs_salaires.paiement.store');

    Route::resource('utilisateurs', UtilisateurController::class)->except(['show']);

    Route::get('/commis', [CommisController::class, 'index'])->name('commis.index');
    Route::post('/commis', [CommisController::class, 'store'])->name('commis.store');
    Route::put('/commis/{commi}', [CommisController::class, 'update'])->name('commis.update');
    Route::delete('/commis/{commi}', [CommisController::class, 'destroy'])->name('commis.destroy');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{id}/pdf', [TicketController::class, 'exportBordereauPdf'])->name('tickets.pdf');
    Route::get('/tickets/unipalm', [TicketController::class, 'unipalm'])->name('tickets.unipalm');
    Route::post('/tickets/associer-fiche', [TicketController::class, 'associerFiche'])->name('tickets.associer_fiche');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::put('/tickets/{id}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::post('/tickets/{id}/valider', [TicketController::class, 'valider'])->name('tickets.valider');
    Route::post('/tickets/{id}/confirm-unipalm', [TicketController::class, 'confirmUnipalm'])->name('tickets.confirm_unipalm');

    Route::get('/vehicules/{vehicule_id}/depenses', [DepenseController::class, 'index'])->name('vehicules.depenses');
    Route::post('/vehicules/{vehicule_id}/depenses', [DepenseController::class, 'store'])->name('vehicules.depenses.store');
    Route::put('/depenses/{depense}', [DepenseController::class, 'update'])->name('depenses.update');
    Route::delete('/depenses/{depense}', [DepenseController::class, 'destroy'])->name('depenses.destroy');
    Route::get('/vehicules/{vehicule_id}/fiche-sortie', [DepenseController::class, 'ficheSortie'])->name('vehicules.fiche_sortie');
    Route::post('/vehicules/{vehicule_id}/fiche-sortie', [DepenseController::class, 'storeFicheSortie'])->name('vehicules.fiche_sortie.store');

    Route::get('/ponts', [PontController::class, 'index'])->name('ponts.index');
    Route::post('/ponts/{id_pont}/etat', [PontController::class, 'updatePontEtat'])->name('ponts.etat.update');
    Route::post('/ponts/{id_pont}/toggle-gerable', [PontController::class, 'toggleGerable'])->name('ponts.toggle_gerable');
    Route::get('/ponts/sorties', [PontController::class, 'sorties'])->name('ponts.sorties');
    Route::get('/ponts/{id_pont}/stock', [PontController::class, 'stock'])->name('ponts.stock');
    Route::post('/ponts/{id_pont}/stock', [PontController::class, 'storeStock'])->name('ponts.stock.store');
    Route::delete('/ponts/{id_pont}/stock/{stock_id}', [PontController::class, 'deleteStock'])->name('ponts.stock.delete');
    Route::post('/ponts/{id_pont}/stock/{stock_id}/etat', [PontController::class, 'toggleStockEtat'])->name('ponts.stock.etat');
    Route::post('/ponts/{id_pont}/stock/{stock_id}/fermer', [PontController::class, 'fermerStock'])->name('ponts.stock.fermer');
    Route::post('/ponts/{id_pont}/stock/{stock_id}/entree', [PontController::class, 'addEntreeStock'])->name('ponts.stock.entree');
    Route::put('/ponts/{id_pont}/stock/{stock_id}/entree/{entree_id}', [PontController::class, 'updateEntreeStock'])->name('ponts.stock.entree.update');
    Route::delete('/ponts/{id_pont}/stock/{stock_id}/entree/{entree_id}', [PontController::class, 'deleteEntreeStock'])->name('ponts.stock.entree.delete');
    Route::post('/ponts/{id_pont}/depense', [PontController::class, 'storeDepense'])->name('ponts.depense.store');
    Route::delete('/ponts/{id_pont}/depense/{depense_id}', [PontController::class, 'destroyDepense'])->name('ponts.depense.delete');

    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/{id_agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::post('/agents/{id_agent}/prix', [AgentController::class, 'storePrix'])->name('agents.prix.store');
    Route::put('/agents/{id_agent}/prix/{prix_id}', [AgentController::class, 'updatePrix'])->name('agents.prix.update');
    Route::delete('/agents/{id_agent}/prix/{prix_id}', [AgentController::class, 'deletePrix'])->name('agents.prix.delete');

    Route::get('/planteurs', [PlanteurController::class, 'index'])->name('planteurs.index');
    Route::get('/planteurs/{id}', [PlanteurController::class, 'show'])->name('planteurs.show');
    Route::put('/planteurs/{id}', [PlanteurController::class, 'update'])->name('planteurs.update');
    Route::delete('/planteurs/{id}', [PlanteurController::class, 'destroy'])->name('planteurs.destroy');

    Route::get('/financements', [FinancementController::class, 'index'])->name('financements.index');
    Route::get('/financements/agents/{id_agent}', [FinancementController::class, 'show'])->name('financements.show');
    Route::post('/financements', [FinancementController::class, 'store'])->name('financements.store');

    Route::get('/usines', [UsineController::class, 'index'])->name('usines.index');

    Route::get('/code-transporteurs', [CodeTransporteurController::class, 'index'])->name('code_transporteurs.index');
    Route::post('/code-transporteurs', [CodeTransporteurController::class, 'store'])->name('code_transporteurs.store');
    Route::get('/code-transporteurs/{id}', [CodeTransporteurController::class, 'show'])->name('code_transporteurs.show');
    Route::put('/code-transporteurs/{id}', [CodeTransporteurController::class, 'update'])->name('code_transporteurs.update');
    Route::delete('/code-transporteurs/{id}', [CodeTransporteurController::class, 'destroy'])->name('code_transporteurs.destroy');
    Route::post('/code-transporteurs/{id}/vehicules', [CodeTransporteurController::class, 'addVehicule'])->name('code_transporteurs.vehicules.add');
    Route::delete('/code-transporteurs/{id}/vehicules/{vehicule_id}', [CodeTransporteurController::class, 'removeVehicule'])->name('code_transporteurs.vehicules.remove');

    Route::get('/depenses', [DepenseController::class, 'listeDepenses'])->name('depenses.liste');
    Route::post('/depenses', [DepenseController::class, 'storeFromList'])->name('depenses.store');
    Route::get('/fiches-sortie', [DepenseController::class, 'listeFichesSortie'])->name('fiches_sortie.index');
    Route::get('/fiches-sortie-non-dechargees', [DepenseController::class, 'listeFichesNonDechargees'])->name('fiches_sortie.non_dechargees');
    Route::get('/fiches-sortie-dechargees', [DepenseController::class, 'listeFichesDechargees'])->name('fiches_sortie.dechargees');
    Route::get('/fiches-sortie/{fiche_id}/pdf', [DepenseController::class, 'exportFicheSortiePdf'])->name('fiches_sortie.pdf');
    Route::get('/fiches-sortie/{fiche_id}', [DepenseController::class, 'showFicheSortie'])->name('fiches_sortie.show');
    Route::get('/api/tickets-conformes', [DepenseController::class, 'getTicketsConformesApi'])->name('api.tickets_conformes');
    Route::get('/api/verifier-stock-pont-produit', [DepenseController::class, 'verifierStockPontProduit'])->name('api.verifier_stock_pont_produit');
    Route::post('/fiches-sortie', [DepenseController::class, 'storeFicheSortieFromList'])->name('fiches_sortie.store');
    Route::post('/fiches-sortie/{fiche_id}/associer-ticket', [DepenseController::class, 'associerTicket'])->name('fiches_sortie.associer_ticket');
    Route::post('/fiches-sortie/{fiche_id}/prix-transport', [DepenseController::class, 'updatePrixTransport'])->name('fiches_sortie.update_prix_transport');
    Route::put('/fiches-sortie/{fiche_id}', [DepenseController::class, 'updateFicheSortie'])->name('fiches_sortie.update');
    Route::put('/fiches-sortie/{fiche_id}/dechargement', [DepenseController::class, 'updateDechargement'])->name('fiches_sortie.dechargement');
    Route::delete('/fiches-sortie/{fiche_id}', [DepenseController::class, 'destroyFicheSortie'])->name('fiches_sortie.destroy');

    // Stocks PGF
    Route::get('/stocks-pgf', [StockPgfController::class, 'index'])->name('stocks_pgf.index');
    Route::post('/stocks-pgf', [StockPgfController::class, 'store'])->name('stocks_pgf.store');
    Route::get('/stocks-pgf/{id}', [StockPgfController::class, 'show'])->name('stocks_pgf.show');
    Route::put('/stocks-pgf/{id}/cloturer', [StockPgfController::class, 'cloturer'])->name('stocks_pgf.cloturer');
    Route::delete('/stocks-pgf/{id}', [StockPgfController::class, 'destroy'])->name('stocks_pgf.destroy');
    Route::post('/stocks-pgf/{id}/entrees', [StockPgfController::class, 'addEntree'])->name('stocks_pgf.entree.add');
    Route::delete('/stocks-pgf/{id}/entrees/{entree_id}', [StockPgfController::class, 'removeEntree'])->name('stocks_pgf.entree.delete');
    Route::get('/stocks-pgf-sorties', [StockPgfController::class, 'sorties'])->name('stocks_pgf.sorties');

    // Bordereaux de stock
    Route::get('/bordereaux-stock', [StockPgfController::class, 'bordereaux'])->name('stocks_pgf.bordereaux');
    Route::post('/bordereaux-stock', [StockPgfController::class, 'storeBordereau'])->name('stocks_pgf.bordereau.store');
    Route::get('/bordereaux-stock/{id}', [StockPgfController::class, 'showBordereau'])->name('stocks_pgf.bordereau.show');
    Route::delete('/bordereaux-stock/{id}', [StockPgfController::class, 'destroyBordereau'])->name('stocks_pgf.bordereau.destroy');
    Route::post('/bordereaux-stock/{id}/associer-tickets', [StockPgfController::class, 'associerTickets'])->name('stocks_pgf.bordereau.associer_tickets');

    // Groupes PGF
    Route::get('/groupes', [GroupeController::class, 'index'])->name('groupes.index');
    Route::post('/groupes', [GroupeController::class, 'store'])->name('groupes.store');
    Route::get('/groupes/{id}', [GroupeController::class, 'show'])->name('groupes.show');
    Route::put('/groupes/{id}', [GroupeController::class, 'update'])->name('groupes.update');
    Route::delete('/groupes/{id}', [GroupeController::class, 'destroy'])->name('groupes.destroy');
    Route::post('/groupes/{id}/agents', [GroupeController::class, 'addAgent'])->name('groupes.agent.add');
    Route::delete('/groupes/{id}/agents/{agent_id}', [GroupeController::class, 'removeAgent'])->name('groupes.agent.remove');
    Route::get('/groupes/{id}/tickets', [GroupeController::class, 'tickets'])->name('groupes.tickets');

    // Groupes Particuliers
    Route::get('/particuliers', [ParticulierController::class, 'index'])->name('particuliers.index');
    Route::post('/particuliers', [ParticulierController::class, 'store'])->name('particuliers.store');
    Route::get('/particuliers/agents', [ParticulierController::class, 'agentsIndex'])->name('particuliers.agents.index');
    Route::post('/particuliers/agents', [ParticulierController::class, 'storeAgent'])->name('particuliers.agents.store');
    Route::put('/particuliers/agents/{agent}', [ParticulierController::class, 'updateAgent'])->name('particuliers.agents.update');
    Route::delete('/particuliers/agents/{agent}', [ParticulierController::class, 'destroyAgent'])->name('particuliers.agents.destroy');
    Route::get('/particuliers/prix-unitaire', [ParticulierPrixController::class, 'index'])->name('particuliers.prix.index');
    Route::get('/particuliers/prix-unitaire/{agent}', [ParticulierPrixController::class, 'show'])->name('particuliers.prix.show');
    Route::post('/particuliers/prix-unitaire/{agent}/prix', [ParticulierPrixController::class, 'storePrix'])->name('particuliers.prix.store');
    Route::put('/particuliers/prix-unitaire/{agent}/prix/{prix}', [ParticulierPrixController::class, 'updatePrix'])->name('particuliers.prix.update');
    Route::delete('/particuliers/prix-unitaire/{agent}/prix/{prix}', [ParticulierPrixController::class, 'deletePrix'])->name('particuliers.prix.delete');
    Route::get('/particuliers/{id}', [ParticulierController::class, 'show'])->name('particuliers.show');
    Route::put('/particuliers/{id}', [ParticulierController::class, 'update'])->name('particuliers.update');
    Route::delete('/particuliers/{id}', [ParticulierController::class, 'destroy'])->name('particuliers.destroy');

    // Chef des chargeurs
    Route::get('/chef-chargeurs', [ChefChargeurController::class, 'index'])->name('chef_chargeurs.index');
    Route::get('/chef-chargeurs/create', [ChefChargeurController::class, 'create'])->name('chef_chargeurs.create');
    Route::post('/chef-chargeurs', [ChefChargeurController::class, 'store'])->name('chef_chargeurs.store');
    Route::get('/chef-chargeurs/{chefChargeur}', [ChefChargeurController::class, 'show'])->name('chef_chargeurs.show');
    Route::get('/chef-chargeurs/{chefChargeur}/edit', [ChefChargeurController::class, 'edit'])->name('chef_chargeurs.edit');
    Route::put('/chef-chargeurs/{chefChargeur}', [ChefChargeurController::class, 'update'])->name('chef_chargeurs.update');
    Route::post('/chef-chargeurs/{chefChargeur}/prix', [ChefChargeurController::class, 'storePrix'])->name('chef_chargeurs.prix.store');
    Route::put('/chef-chargeurs/{chefChargeur}/prix/{prix}', [ChefChargeurController::class, 'updatePrix'])->name('chef_chargeurs.prix.update');
    Route::delete('/chef-chargeurs/{chefChargeur}/prix/{prix}', [ChefChargeurController::class, 'destroyPrix'])->name('chef_chargeurs.prix.destroy');
    Route::delete('/chef-chargeurs/{chefChargeur}', [ChefChargeurController::class, 'destroy'])->name('chef_chargeurs.destroy');

    // Chargeurs
    Route::get('/chargeurs', [ChargeurController::class, 'index'])->name('chargeurs.index');
    Route::post('/chargeurs', [ChargeurController::class, 'store'])->name('chargeurs.store');
    Route::put('/chargeurs/{chargeur}', [ChargeurController::class, 'update'])->name('chargeurs.update');
    Route::delete('/chargeurs/{chargeur}', [ChargeurController::class, 'destroy'])->name('chargeurs.destroy');

    // Chauffeurs
    Route::get('/chauffeurs', [ChauffeurController::class, 'index'])->name('chauffeurs.index');
    Route::post('/chauffeurs', [ChauffeurController::class, 'store'])->name('chauffeurs.store');
    Route::put('/chauffeurs/{chauffeur}', [ChauffeurController::class, 'update'])->name('chauffeurs.update');
    Route::delete('/chauffeurs/{chauffeur}', [ChauffeurController::class, 'destroy'])->name('chauffeurs.destroy');

    Route::get('/transporteurs', [TransporteurController::class, 'index'])->name('transporteurs.index');
    Route::post('/transporteurs', [TransporteurController::class, 'store'])->name('transporteurs.store');
    Route::get('/transporteurs/{transporteur}', [TransporteurController::class, 'show'])->name('transporteurs.show');
    Route::get('/transporteurs/{transporteur}/ajouter-camions', [TransporteurController::class, 'ajouterCamions'])->name('transporteurs.camions.ajouter');
    Route::post('/transporteurs/{transporteur}/camions', [TransporteurController::class, 'assignerCamions'])->name('transporteurs.camions.assigner');
    Route::delete('/transporteurs/{transporteur}/camions/{vehicule_id}', [TransporteurController::class, 'retirerCamion'])->name('transporteurs.camions.retirer');
    Route::put('/transporteurs/{transporteur}', [TransporteurController::class, 'update'])->name('transporteurs.update');
    Route::delete('/transporteurs/{transporteur}', [TransporteurController::class, 'destroy'])->name('transporteurs.destroy');

    // Bilan par véhicule
    Route::get('/bilan-vehicule', [BilanVehiculeController::class, 'index'])->name('bilan-vehicule.index');
    Route::get('/bilan-vehicule/{vehicule_id}', [BilanVehiculeController::class, 'show'])->name('bilan-vehicule.show');

    // Pisteurs
    Route::get('/pisteurs', [PisteurController::class, 'index'])->name('pisteurs.index');
    Route::post('/pisteurs', [PisteurController::class, 'store'])->name('pisteurs.store');
    Route::get('/pisteurs/{pisteur}', [PisteurController::class, 'show'])->name('pisteurs.show');
    Route::put('/pisteurs/{pisteur}', [PisteurController::class, 'update'])->name('pisteurs.update');
    Route::delete('/pisteurs/{pisteur}', [PisteurController::class, 'destroy'])->name('pisteurs.destroy');
    Route::post('/pisteurs/{pisteur}/prix', [PisteurController::class, 'storePrix'])->name('pisteurs.prix.store');
    Route::put('/pisteurs/{pisteur}/prix/{prix}', [PisteurController::class, 'updatePrix'])->name('pisteurs.prix.update');
    Route::delete('/pisteurs/{pisteur}/prix/{prix}', [PisteurController::class, 'destroyPrix'])->name('pisteurs.prix.destroy');

    // Montant Agents particuliers (locaux)
    Route::get('/gestion-financiere/montant-particulier', [MontantParticulierController::class, 'index'])->name('gestionfinanciere.montant_particulier');
    Route::get('/gestion-financiere/particulier-financier/{agent}', [MontantParticulierController::class, 'show'])->name('gestionfinanciere.particulier.show');
    Route::post('/gestion-financiere/particulier-financier/{agent}/paiement', [MontantParticulierController::class, 'storePaiement'])->name('gestionfinanciere.paiement_particulier.store');

    // Montant Agent (Pisteur)
    Route::get('/gestion-financiere/montant-agent', [MontantAgentController::class, 'index'])->name('gestionfinanciere.montant_agent');
    Route::get('/gestion-financiere/synthese-produit', [MontantAgentController::class, 'syntheseProduit'])->name('gestionfinanciere.synthese_produit');
    Route::get('/gestion-financiere/agent-financier/{id_agent}', [MontantAgentController::class, 'show'])->name('gestionfinanciere.agent.show');
    Route::get('/gestion-financiere/agent-financier/{id_agent}/bordereaux/fiches-eligibles', [MontantAgentController::class, 'fichesEligiblesBordereau'])->name('gestionfinanciere.agent.bordereau.fiches');
    Route::post('/gestion-financiere/agent-financier/{id_agent}/bordereaux', [MontantAgentController::class, 'storeBordereau'])->name('gestionfinanciere.agent.bordereau.store');
    Route::get('/gestion-financiere/agent-financier/{id_agent}/bordereaux/{id}', [MontantAgentController::class, 'showBordereau'])->name('gestionfinanciere.agent.bordereau.show');
    Route::get('/gestion-financiere/agent-financier/{id_agent}/bordereaux/{id}/pdf', [MontantAgentController::class, 'exportBordereauPdf'])->name('gestionfinanciere.agent.bordereau.pdf');
    Route::delete('/gestion-financiere/agent-financier/{id_agent}/bordereaux/{id}', [MontantAgentController::class, 'destroyBordereau'])->name('gestionfinanciere.agent.bordereau.destroy');
    Route::post('/gestion-financiere/agent-financier/{id_agent}/bordereaux/{id}/paiement', [MontantAgentController::class, 'storePaiementBordereau'])->name('gestionfinanciere.agent.bordereau.paiement.store');
    Route::post('/gestion-financiere/agent-financier/{id_agent}/avance', [MontantAgentController::class, 'storeAvance'])->name('gestionfinanciere.agent.avance.store');
    Route::post('/gestion-financiere/agent-financier/{id_agent}/ticket/{id_ticket}/produit', [MontantAgentController::class, 'updateProduitTicket'])->name('gestionfinanciere.agent.ticket.produit');
    Route::post('/gestion-financiere/agent-financier/{id_agent}/paiement', [MontantAgentController::class, 'storePaiement'])->name('gestionfinanciere.paiement_agent.store');

    // Montant Chef Chargeur
    Route::get('/gestion-financiere/montant-chef-chargeur', [MontantChefChargeurController::class, 'index'])->name('gestionfinanciere.montant_chef_chargeur');
    Route::get('/gestion-financiere/chef-chargeur/{id}', [MontantChefChargeurController::class, 'show'])->name('gestionfinanciere.chef_chargeur.show');
    Route::get('/gestion-financiere/chef-chargeur/{id}/pdf', [MontantChefChargeurController::class, 'exportPdf'])->name('gestionfinanciere.chef_chargeur.pdf');
    Route::post('/gestion-financiere/montant-chef-chargeur/{chefChargeur}/paiement', [MontantChefChargeurController::class, 'storePaiement'])->name('gestionfinanciere.paiement_chef_chargeur.store');

    // Montant Fournisseur
    Route::get('/gestion-financiere/montant-fournisseur', [MontantFournisseurController::class, 'index'])->name('gestionfinanciere.montant_fournisseur');
    Route::get('/gestion-financiere/fournisseur/{nom}', [MontantFournisseurController::class, 'show'])->name('gestionfinanciere.fournisseur.show');
    Route::get('/gestion-financiere/fournisseur/{nom}/pdf', [MontantFournisseurController::class, 'exportPdf'])->name('gestionfinanciere.fournisseur.pdf');
    Route::post('/gestion-financiere/montant-fournisseur/paiement', [MontantFournisseurController::class, 'storePaiement'])->name('gestionfinanciere.montant_fournisseur.paiement');

    // Montant Transporteurs
    Route::get('/gestion-financiere/montant-transporteur', [MontantTransporteurController::class, 'index'])->name('gestionfinanciere.montant_transporteur');
    Route::get('/gestion-financiere/transporteur/{transporteur}', [MontantTransporteurController::class, 'show'])->name('gestionfinanciere.transporteur.show');
    Route::post('/gestion-financiere/montant-transporteur/{transporteur}/paiement', [MontantTransporteurController::class, 'storePaiementGestion'])->name('gestionfinanciere.paiement_transporteur.store');
    Route::get('/gestion-financiere/transporteur/{transporteur}/historique-paiements', [MontantTransporteurController::class, 'historiquePaiements'])->name('gestionfinanciere.transporteur.historique');
    Route::get('/gestion-financiere/transporteur/vehicule/{matricule}', [MontantTransporteurController::class, 'showVehicule'])->name('gestionfinanciere.transporteur.vehicule');
    Route::put('/gestion-financiere/transporteur/fiche/{ficheId}/pu', [MontantTransporteurController::class, 'updatePU'])->name('gestionfinanciere.transporteur.updatePU');
    Route::post('/gestion-financiere/transporteur/fiche/{ficheId}/paiement', [MontantTransporteurController::class, 'storePaiement'])->name('gestionfinanciere.transporteur.paiement');
    Route::get('/gestion-financiere/transporteur/{transporteur}/bordereaux/fiches-eligibles', [MontantTransporteurController::class, 'fichesEligiblesBordereau'])->name('gestionfinanciere.transporteur.bordereau.fiches');
    Route::post('/gestion-financiere/transporteur/{transporteur}/bordereaux', [MontantTransporteurController::class, 'storeBordereau'])->name('gestionfinanciere.transporteur.bordereau.store');
    Route::get('/gestion-financiere/transporteur/{transporteur}/bordereaux/{id}', [MontantTransporteurController::class, 'showBordereau'])->name('gestionfinanciere.transporteur.bordereau.show');
    Route::get('/gestion-financiere/transporteur/{transporteur}/bordereaux/{id}/pdf', [MontantTransporteurController::class, 'exportBordereauPdf'])->name('gestionfinanciere.transporteur.bordereau.pdf');
    Route::delete('/gestion-financiere/transporteur/{transporteur}/bordereaux/{id}', [MontantTransporteurController::class, 'destroyBordereau'])->name('gestionfinanciere.transporteur.bordereau.destroy');
    Route::post('/gestion-financiere/transporteur/{transporteur}/bordereaux/{id}/paiement', [MontantTransporteurController::class, 'storePaiementBordereau'])->name('gestionfinanciere.transporteur.bordereau.paiement.store');

    // Services
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // Fournisseurs
    Route::get('/fournisseurs', [FournisseurController::class, 'index'])->name('fournisseurs.index');
    Route::post('/fournisseurs', [FournisseurController::class, 'store'])->name('fournisseurs.store');
    Route::put('/fournisseurs/{fournisseur}', [FournisseurController::class, 'update'])->name('fournisseurs.update');
    Route::delete('/fournisseurs/{fournisseur}', [FournisseurController::class, 'destroy'])->name('fournisseurs.destroy');

    // Parcs
    Route::get('/parcs', [ParcController::class, 'index'])->name('parcs.index');
    Route::post('/parcs', [ParcController::class, 'store'])->name('parcs.store');
    Route::put('/parcs/{parc}', [ParcController::class, 'update'])->name('parcs.update');
    Route::delete('/parcs/{parc}', [ParcController::class, 'destroy'])->name('parcs.destroy');

    // Approvisionnements
    Route::get('/approvisionnements', [ApprovisionnementController::class, 'index'])->name('approvisionnements.index');
    Route::get('/approvisionnements/sorties', [ApprovisionnementController::class, 'sorties'])->name('approvisionnements.sorties');
    Route::post('/approvisionnements', [ApprovisionnementController::class, 'store'])->name('approvisionnements.store');
    Route::get('/approvisionnements/{approvisionnement}', [ApprovisionnementController::class, 'show'])->name('approvisionnements.show');
    Route::put('/approvisionnements/{approvisionnement}', [ApprovisionnementController::class, 'update'])->name('approvisionnements.update');
    Route::delete('/approvisionnements/{approvisionnement}', [ApprovisionnementController::class, 'destroy'])->name('approvisionnements.destroy');
});
