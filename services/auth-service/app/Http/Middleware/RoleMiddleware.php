<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user;

        if ($user === null || ! $user->role) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur ou rôle introuvable.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($user->role->slug(), $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Permissions insuffisantes.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
