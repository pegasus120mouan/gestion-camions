<?php

namespace App\Providers;

use App\Models\Pesee;
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

            $showSoldeChefBanner = request()->routeIs('gestionfinanciere.*', 'solde_chef_equipe.*');
            $soldeChef = null;
            $soldeChefToken = '';

            if ($showSoldeChefBanner) {
                $soldeChefToken = trim((string) (
                    session('chef_equipe_token')
                    ?? auth()->user()?->chef_equipe_token
                    ?? config('services.external_auth.default_chef_equipe_token', '')
                ));

                if ($soldeChefToken !== '') {
                    $soldeChef = app(SoldeChefEquipeService::class)->getSoldeByToken($soldeChefToken);
                }
            }

            $view->with([
                'peseesTodayCount' => $peseesTodayCount,
                'showSoldeChefBanner' => $showSoldeChefBanner,
                'soldeChef' => $soldeChef,
                'soldeChefToken' => $soldeChefToken,
            ]);
        });
    }
}
