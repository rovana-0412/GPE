<?php

use App\Exceptions\PatrimoineServiceException;
use App\Http\Middleware\JwtAuth;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => JwtAuth::class,
            'role' => RoleMiddleware::class,
        ]);
        $middleware->prependToGroup('api', 'throttle:api');
        // Render termine le TLS et ajoute X-Forwarded-* (IP client réelle).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PatrimoineServiceException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status);
        });
    })->create();
