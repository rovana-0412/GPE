<?php

use App\Exceptions\ServiceAuthenticationException;
use App\Exceptions\ServiceUnavailableException;
use App\Http\Middleware\EnsureJwtAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => EnsureJwtAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ServiceAuthenticationException $exception, Request $request) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Votre session a expiré. Veuillez vous reconnecter.',
                ], 401);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Votre session a expiré. Veuillez vous reconnecter.',
            ]);
        });

        $exceptions->render(function (ServiceUnavailableException $exception, Request $request) {
            $request->attributes->set('service_unavailable', true);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Le service demandé est momentanément indisponible. Réessayez plus tard.',
                ], 503);
            }

            return response()->view('errors.service-unavailable', [
                'service' => $exception->service,
            ], 503);
        });
    })->create();
