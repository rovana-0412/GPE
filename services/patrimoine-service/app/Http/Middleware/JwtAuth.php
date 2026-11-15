<?php

namespace App\Http\Middleware;

use Closure;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use UnexpectedValueException;

class JwtAuth
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return response()->json([
                'success' => false,
                'message' => 'Token manquant.',
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $secret = config('jwt.secret');

        if (! is_string($secret) || strlen($secret) < 32) {
            return response()->json([
                'success' => false,
                'message' => 'La configuration du service est incomplète.',
            ], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $payload = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (UnexpectedValueException|DomainException|InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré.',
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        if (! isset($payload->sub, $payload->role) || ! property_exists($payload, 'service_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Les informations du token sont incomplètes.',
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('jwt_payload', (array) $payload);

        return $next($request);
    }
}
