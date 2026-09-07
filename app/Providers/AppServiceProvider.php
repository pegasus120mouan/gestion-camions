<?php

namespace App\Providers;

use App\Models\Pesee;
use App\Services\ChefEquipeContext;
use App\Services\SoldeChefEquipeService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
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

        View::composer('layout.main', function ($view) {
            $peseesTodayCount = Pesee::query()
                ->whereDate('pese_le', Carbon::today())
                ->count();

            $ticketsEnAttenteCount = 0;
            $ticketsEnAttenteProfessionnelCount = 0;
            $ticketsEnAttenteParticulierCount = 0;
            try {
                $counts = app(\App\Services\MesTicketsService::class)
                    ->countsTicketsEnAttente(request());
                $ticketsEnAttenteCount = (int) ($counts['total'] ?? 0);
                $ticketsEnAttenteProfessionnelCount = (int) ($counts['professionnel'] ?? 0);
                $ticketsEnAttenteParticulierCount = (int) ($counts['particulier'] ?? 0);
            } catch (\Throwable) {
                $ticketsEnAttenteCount = 0;
                $ticketsEnAttenteProfessionnelCount = 0;
                $ticketsEnAttenteParticulierCount = 0;
            }

            $chefSession = app(\App\Services\ChefEquipeSession::class);
            $authChef = $chefSession->chef();

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
            ]);
        });
    }
}
