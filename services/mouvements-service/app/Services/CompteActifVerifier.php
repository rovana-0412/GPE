<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Vérifie auprès de l'auth-service qu'un compte est toujours actif.
 * Le JWT ne fait que transporter l'identité : le statut est revérifié
 * en base via GET /api/me (protégé par jwt.auth côté auth-service).
 *
 * Retour : true = actif, false = désactivé, null = statut indéterminé
 * (auth-service injoignable ou réponse inattendue) — jamais mis en cache.
 */
class CompteActifVerifier
{
    private const TTL_CACHE = 30;

    public function estActif(string $token, int $userId): ?bool
    {
        $cacheKey = "compte_actif:{$userId}";
        $cached = Cache::get($cacheKey);

        if ($cached === 'actif') {
            return true;
        }

        if ($cached === 'inactif') {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(3)
                ->acceptJson()
                ->get(rtrim((string) config('services.auth.url'), '/').'/api/me');
        } catch (\Throwable) {
            return null;
        }

        if ($response->successful()) {
            Cache::put($cacheKey, 'actif', self::TTL_CACHE);

            return true;
        }

        if ($response->status() === 401 || $response->status() === 403) {
            Cache::put($cacheKey, 'inactif', self::TTL_CACHE);

            return false;
        }

        return null;
    }
}
