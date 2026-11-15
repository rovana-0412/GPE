<?php

namespace App\Services;

use App\Exceptions\PatrimoineServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PatrimoineClient
{
    public function bien(int $bienId, string $token): ?array
    {
        $response = $this->send('GET', '/api/biens/'.$bienId, $token);

        if ($response->notFound()) {
            return null;
        }

        if (! $response->successful()) {
            throw new PatrimoineServiceException(
                'Le bien n’a pas pu être vérifié auprès du service patrimoine.',
                $response->serverError() ? 503 : 502,
            );
        }

        $bien = $response->json('data');

        if (! is_array($bien) || ! isset($bien['id'], $bien['service_id'])) {
            throw new PatrimoineServiceException('La réponse du service patrimoine est invalide.', 502);
        }

        return $bien;
    }

    public function serviceExiste(int $serviceId, string $token): bool
    {
        $response = $this->send('GET', '/api/services/'.$serviceId, $token);

        if ($response->notFound()) {
            return false;
        }

        if (! $response->successful()) {
            throw new PatrimoineServiceException(
                'Le service destinataire n’a pas pu être vérifié.',
                $response->serverError() ? 503 : 502,
            );
        }

        return is_array($response->json('data'));
    }

    public function changerService(int $bienId, int $serviceId, string $token): void
    {
        $response = $this->send('PATCH', '/api/biens/'.$bienId, $token, [
            'service_id' => $serviceId,
        ]);

        if (! $response->successful()) {
            throw new PatrimoineServiceException(
                'Le bien n’a pas pu être mis à jour; le transfert reste approuvé.',
                $response->serverError() ? 503 : 502,
            );
        }
    }

    private function send(string $method, string $path, string $token, array $data = []): Response
    {
        $baseUrl = config('services.patrimoine.url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new PatrimoineServiceException('L’adresse du service patrimoine n’est pas configurée.');
        }

        try {
            return Http::acceptJson()
                ->withToken($token)
                ->timeout(5)
                ->send($method, rtrim($baseUrl, '/').$path, $data === [] ? [] : ['json' => $data]);
        } catch (ConnectionException $exception) {
            throw new PatrimoineServiceException(
                'Le service patrimoine est momentanément indisponible.',
                503,
                $exception,
            );
        }
    }
}
