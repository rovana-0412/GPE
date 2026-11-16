<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MouvementNotificationClient
{
    public function envoyer(
        int $utilisateurId,
        string $type,
        string $titre,
        string $message,
        array $donnees,
        string $token,
    ): void {
        $baseUrl = config('services.mouvements.url');
        if (! is_string($baseUrl) || $baseUrl === '') {
            Log::warning('Notification non transmise : MOUVEMENTS_URL absent.', [
                'type' => $type,
                'utilisateur_id' => $utilisateurId,
            ]);

            return;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(3)
                ->post(rtrim($baseUrl, '/').'/api/notifications', [
                    'utilisateur_id' => $utilisateurId,
                    'type' => $type,
                    'titre' => $titre,
                    'message' => $message,
                    'donnees' => $donnees,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Notification non transmise : mouvements-service inaccessible.', [
                'type' => $type,
                'utilisateur_id' => $utilisateurId,
                'exception' => $exception->getMessage(),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('Notification refusée par mouvements-service.', [
                'type' => $type,
                'utilisateur_id' => $utilisateurId,
                'status' => $response->status(),
            ]);
        }
    }
}
