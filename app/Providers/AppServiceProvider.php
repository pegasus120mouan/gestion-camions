<?php

namespace App\Providers;

use App\Models\Pesee;
use App\Services\AccesPlateforme;
use App\Services\ChefEquipeContext;
use App\Services\SoldeChefEquipeService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Utiliser Bootstrap 5 pour la pagination
        Paginator::useBootstrapFive();

        Blade::if('peut', function (string ...$permissions) {
            return app(AccesPlateforme::class)->peut(...$permissions);
        });

        View::composer('layout.main', function ($view) {
            $peseesTodayCount = Pesee::query()
                ->whereDate('pese_le', Carbon::today())
                ->count();

            $ticketsEnAttenteCount = 0;
            $ticketsEnAttenteProfessionnelCount = 0;
            $ticketsEnAttenteParticulierCount = 0;
            $ticketsSyncNecessaire = false;
            try {
                $mesTickets = app(\App\Services\MesTicketsService::class);
                $counts = $mesTickets->countsTicketsEnAttente(request());
                $ticketsEnAttenteCount = (int) ($counts['total'] ?? 0);
                $ticketsEnAttenteProfessionnelCount = (int) ($counts['professionnel'] ?? 0);
                $ticketsEnAttenteParticulierCount = (int) ($counts['particulier'] ?? 0);
                $ticketsSyncNecessaire = $mesTickets->synchronisationTicketsNecessaire(request());
            } catch (\Throwable) {
                $ticketsEnAttenteCount = 0;
                $ticketsEnAttenteProfessionnelCount = 0;
                $ticketsEnAttenteParticulierCount = 0;
            }

            $chefSession = app(\App\Services\ChefEquipeSession::class);
            $acces = app(AccesPlateforme::class);
            $authChef = $chefSession->chef();
            $peutParametres = app(\App\Services\ParametresAccess::class)->allowed(request());
            $peutGererAcces = $acces->peutGererAcces(request());
            $authIdentite = $acces->identiteAffichee(request());
            $authProfil = $acces->profilCourant(request());

            $showSoldeChefBanner = request()->routeIs('gestionfinanciere.*', 'solde_chef_equipe.*', 'effectuer_paiement.*');
            $soldeChef = null;
            $soldeChefToken = '';

            if ($showSoldeChefBanner) {
                $chefContext = app(ChefEquipeContext::class);
                $soldeChefToken = $chefContext->resolveToken(request());

                if ($soldeChefToken !== '') {
                    $soldeChef = app(SoldeChefEquipeService::class)->getSoldeByToken($soldeChefToken);
                }
            }

            $view->with([
                'peseesTodayCount' => $peseesTodayCount,
                'ticketsEnAttenteCount' => $ticketsEnAttenteCount,
                'ticketsEnAttenteProfessionnelCount' => $ticketsEnAttenteProfessionnelCount,
                'ticketsEnAttenteParticulierCount' => $ticketsEnAttenteParticulierCount,
                'showSoldeChefBanner' => $showSoldeChefBanner,
                'soldeChef' => $soldeChef,
                'soldeChefToken' => $soldeChefToken,
                'authChef' => $authChef,
                'authIdentite' => $authIdentite,
                'authProfil' => $authProfil,
                'peutParametres' => $peutParametres,
                'peutGererAcces' => $peutGererAcces,
                'ticketsSyncNecessaire' => $ticketsSyncNecessaire,
            ]);
        });
    }
}
