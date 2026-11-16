<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $payload = $request->attributes->get('jwt_payload');

        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Token manquant ou invalide.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userRole = $payload['role'] ?? null;

        if (! $userRole) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé dans le token.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($userRole === 'administrateur-general') {
            return $next($request);
        }

        if (! in_array($userRole, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Permissions insuffisantes.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
