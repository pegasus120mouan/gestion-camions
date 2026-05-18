<?php

namespace App\Providers;

use App\Models\Pesee;
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

            $view->with('peseesTodayCount', $peseesTodayCount);
        });
    }
}
