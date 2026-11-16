<?php

namespace App\Providers;

use App\Services\MicroserviceClient;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        View::composer('*', function (ViewInstance $view): void {
            $utilisateur = session('user', []);
            $role = data_get($utilisateur, 'role_slug', data_get($utilisateur, 'role'));
            $roleSlug = Str::slug((string) $role);

            $view->with('peutGererPatrimoine', in_array($roleSlug, [
                'administrateur-general',
                'gestionnaire-patrimoine',
            ], true));
            $view->with('peutGererUtilisateurs', $roleSlug === 'administrateur-general');
            $view->with('parametresPlateforme', array_replace(
                config('platform.defaults', []),
                session('platform_settings', []),
            ));
        });

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
