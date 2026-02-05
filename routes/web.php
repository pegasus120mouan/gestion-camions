<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\GestionFinanciereController;
use App\Http\Controllers\PontPesageController;
use App\Http\Controllers\PeseeController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\UtilisateurController;
use Illuminate\Support\Facades\Route;

Route::get('/index.html', function () {
    return redirect('/');
});

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $authUser = auth()->user();
        $nombreCamions = 0;
        $totalDepenses = 0;

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
                } catch (\Throwable $e) {
                    $nombreCamions = 0;
                }
            }
        }

        return view('dashboard', [
            'nombreCamions' => $nombreCamions,
            'totalDepenses' => $totalDepenses,
        ]);
    })->name('dashboard');

    Route::get('/utilisateurs/admins', [UtilisateurController::class, 'admins'])->name('utilisateurs.admins');
    Route::get('/utilisateurs/agents', [UtilisateurController::class, 'agents'])->name('utilisateurs.agents');
    Route::get('/utilisateurs/chauffeurs', [UtilisateurController::class, 'chauffeurs'])->name('utilisateurs.chauffeurs');

    Route::resource('camions', CamionController::class)->except(['create']);
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

    Route::get('/vehicules/{vehicule_id}/depenses', [DepenseController::class, 'index'])->name('vehicules.depenses');
    Route::post('/vehicules/{vehicule_id}/depenses', [DepenseController::class, 'store'])->name('vehicules.depenses.store');
});
