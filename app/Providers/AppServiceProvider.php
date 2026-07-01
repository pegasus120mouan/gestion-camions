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

            $chefSession = app(\App\Services\ChefEquipeSession::class);
            $authChef = $chefSession->chef();

            $showSoldeChefBanner = request()->routeIs('gestionfinanciere.*', 'solde_chef_equipe.*');
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
                'showSoldeChefBanner' => $showSoldeChefBanner,
                'soldeChef' => $soldeChef,
                'soldeChefToken' => $soldeChefToken,
                'authChef' => $authChef,
            ]);
        });
    }
}
