<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RoleMiddleware
{
    /**
     * Rôles autorisés et leurs définitions
     */
    private array $roles = [
        'administrateur-general' => 1,
        'gestionnaire-patrimoine' => 2,
        'responsable-ministeriel' => 3,
        'agent-gestionnaire' => 4,
        'auditeur' => 5,
    ];

    /**
     * Gérer la vérification du rôle
     */
    public function handle(Request $request, Closure $next, string $role): SymfonyResponse
    {
        $payload = $request->attributes->get('jwt_payload');

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Token manquant ou invalide.',
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $userRole = $payload['role'] ?? null;

        if (!$userRole) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé dans le token.',
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        // L'administrateur général a tous les droits
        if ($userRole === 'administrateur-general') {
            return $next($request);
        }

        // Vérifier si le rôle de l'utilisateur correspond au rôle requis
        if ($userRole !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Permissions insuffisantes.',
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
