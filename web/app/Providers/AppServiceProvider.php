<?php

namespace App\Providers;

use App\Services\MicroserviceClient;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        View::composer('partials.sidebar', function (ViewInstance $view): void {
            if (! session()->has('jwt_token')) {
                return;
            }

            if (request()->attributes->get('service_unavailable')) {
                $view->with('transferts_en_attente', null);

                return;
            }

            $stats = request()->attributes->get('mouvements_stats');

            if (! is_array($stats)) {
                $stats = app(MicroserviceClient::class)
                    ->request('mouvements', 'GET', '/api/stats')
                    ->json('data', []);
            }

            $view->with('transferts_en_attente', (int) ($stats['transferts_en_attente'] ?? 0));
        });
    }
}
