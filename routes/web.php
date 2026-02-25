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
use App\Http\Controllers\FinancementController;
use App\Http\Controllers\UsineController;
use App\Http\Controllers\CodeTransporteurController;
use App\Http\Controllers\StockPgfController;
use App\Http\Controllers\GroupeController;
use App\Http\Controllers\PlanteurController;
use App\Http\Controllers\MinioProxyController;
use App\Http\Controllers\ChefChargeurController;
use App\Http\Controllers\ChargeurController;
use App\Http\Controllers\MontantChefChargeurController;
use Illuminate\Support\Facades\Route;

Route::get('/index.html', function () {
    return redirect('/');
});

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
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

        return view('dashboard', [
            'nombreCamions' => $nombreCamions,
            'totalDepenses' => $totalDepenses,
            'nombreTickets' => $nombreTickets,
        ]);
    })->name('dashboard');

    Route::get('/utilisateurs/admins', [UtilisateurController::class, 'admins'])->name('utilisateurs.admins');
    Route::get('/utilisateurs/agents', [UtilisateurController::class, 'agents'])->name('utilisateurs.agents');
    Route::get('/utilisateurs/chauffeurs', [UtilisateurController::class, 'chauffeurs'])->name('utilisateurs.chauffeurs');

    Route::resource('camions', CamionController::class)->except(['create']);
    Route::get('/camions-pgf', [CamionController::class, 'camionsPgf'])->name('camions.camions_pgf');
    Route::post('/camions/assigner-groupe', [CamionController::class, 'assignerGroupe'])->name('camions.assigner_groupe');
    Route::delete('/camions/{vehicule_id}/retirer-groupe', [CamionController::class, 'retirerGroupe'])->name('camions.retirer_groupe');
    Route::resource('ponts_pesage', PontPesageController::class)->except(['create']);
    Route::get('pesees/{pesee}/ticket', [PeseeController::class, 'ticket'])->name('pesees.ticket');
    Route::post('pesees/{pesee}/validate', [PeseeController::class, 'validateStatus'])->name('pesees.validate');
    Route::post('pesees/{pesee}/cancel', [PeseeController::class, 'cancel'])->name('pesees.cancel');
    Route::resource('pesees', PeseeController::class)->except(['create']);
    Route::resource('produits', ProduitController::class)->except(['create']);

    Route::get('/gestionfinanciere', [GestionFinanciereController::class, 'index'])->name('gestionfinanciere.index');
    Route::post('/gestionfinanciere', [GestionFinanciereController::class, 'store'])->name('gestionfinanciere.store');
    Route::delete('/gestionfinanciere/{mouvement}', [GestionFinanciereController::class, 'destroy'])->name('gestionfinanciere.destroy');

    Route::get('/gestionfinanciere/sorties', [GestionFinanciereController::class, 'sorties'])->name('gestionfinanciere.sorties');
    Route::post('/gestionfinanciere/sorties', [GestionFinanciereController::class, 'storeSortie'])->name('gestionfinanciere.sorties.store');

    Route::resource('utilisateurs', UtilisateurController::class)->except(['show']);

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/unipalm', [TicketController::class, 'unipalm'])->name('tickets.unipalm');
    Route::post('/tickets/associer-fiche', [TicketController::class, 'associerFiche'])->name('tickets.associer_fiche');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::put('/tickets/{id}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::post('/tickets/{id}/confirm-unipalm', [TicketController::class, 'confirmUnipalm'])->name('tickets.confirm_unipalm');

    Route::get('/vehicules/{vehicule_id}/depenses', [DepenseController::class, 'index'])->name('vehicules.depenses');
    Route::post('/vehicules/{vehicule_id}/depenses', [DepenseController::class, 'store'])->name('vehicules.depenses.store');
    Route::get('/vehicules/{vehicule_id}/fiche-sortie', [DepenseController::class, 'ficheSortie'])->name('vehicules.fiche_sortie');
    Route::post('/vehicules/{vehicule_id}/fiche-sortie', [DepenseController::class, 'storeFicheSortie'])->name('vehicules.fiche_sortie.store');

    Route::get('/ponts', [PontController::class, 'index'])->name('ponts.index');
    Route::get('/ponts/sorties', [PontController::class, 'sorties'])->name('ponts.sorties');
    Route::get('/ponts/{id_pont}/stock', [PontController::class, 'stock'])->name('ponts.stock');
    Route::post('/ponts/{id_pont}/stock', [PontController::class, 'storeStock'])->name('ponts.stock.store');
    Route::delete('/ponts/{id_pont}/stock/{stock_id}', [PontController::class, 'deleteStock'])->name('ponts.stock.delete');

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
    Route::get('/fiches-sortie/{fiche_id}', [DepenseController::class, 'showFicheSortie'])->name('fiches_sortie.show');
    Route::get('/api/tickets-conformes', [DepenseController::class, 'getTicketsConformesApi'])->name('api.tickets_conformes');
    Route::post('/fiches-sortie', [DepenseController::class, 'storeFicheSortieFromList'])->name('fiches_sortie.store');
    Route::post('/fiches-sortie/{fiche_id}/associer-ticket', [DepenseController::class, 'associerTicket'])->name('fiches_sortie.associer_ticket');
    Route::post('/fiches-sortie/{fiche_id}/prix-transport', [DepenseController::class, 'updatePrixTransport'])->name('fiches_sortie.update_prix_transport');
    Route::put('/fiches-sortie/{fiche_id}', [DepenseController::class, 'updateFicheSortie'])->name('fiches_sortie.update');
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
    Route::delete('/groupes/{id}', [GroupeController::class, 'destroy'])->name('groupes.destroy');
    Route::post('/groupes/{id}/agents', [GroupeController::class, 'addAgent'])->name('groupes.agent.add');
    Route::delete('/groupes/{id}/agents/{agent_id}', [GroupeController::class, 'removeAgent'])->name('groupes.agent.remove');
    Route::get('/groupes/{id}/tickets', [GroupeController::class, 'tickets'])->name('groupes.tickets');

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

    // Montant Chef Chargeur
    Route::get('/gestion-financiere/montant-chef-chargeur', [MontantChefChargeurController::class, 'index'])->name('gestionfinanciere.montant_chef_chargeur');
    Route::post('/gestion-financiere/montant-chef-chargeur/{chefChargeur}/paiement', [MontantChefChargeurController::class, 'storePaiement'])->name('gestionfinanciere.paiement_chef_chargeur.store');
});
