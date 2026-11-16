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

    public function biens(?int $serviceId, string $token): array
    {
        $biens = [];
        $page = 1;
        $lastPage = 1;

        do {
            $query = http_build_query(array_filter([
                'service_id' => $serviceId,
                'per_page' => 100,
                'page' => $page,
            ], static fn ($value): bool => $value !== null));
            $response = $this->send('GET', '/api/biens?'.$query, $token);

            if (! $response->successful()) {
                throw new PatrimoineServiceException(
                    'Les biens de l’inventaire n’ont pas pu être chargés.',
                    $response->serverError() ? 503 : 502,
                );
            }

            $data = $response->json();
            if (
                ! is_array($data['data'] ?? null)
                || ! is_numeric($data['last_page'] ?? null)
                || (int) $data['last_page'] > 10000
            ) {
                throw new PatrimoineServiceException('La liste des biens reçue est invalide.', 502);
            }
            foreach ($data['data'] as $asset) {
                if (! is_array($asset) || ! isset($asset['id']) || ! is_numeric($asset['id'])) {
                    throw new PatrimoineServiceException('La liste des biens reçue est invalide.', 502);
                }
            }

            array_push($biens, ...$data['data']);
            $lastPage = (int) $data['last_page'];
            $page++;
        } while ($page <= $lastPage);

        return $biens;
    }

    public function serviceExiste(int $serviceId, string $token): bool
    {
        return $this->service($serviceId, $token) !== null;
    }

    public function service(int $serviceId, string $token): ?array
    {
        $response = $this->send('GET', '/api/services/'.$serviceId, $token);

        if ($response->notFound()) {
            return null;
        }

        if (! $response->successful()) {
            throw new PatrimoineServiceException(
                'Le service n’a pas pu être vérifié auprès du service patrimoine.',
                $response->serverError() ? 503 : 502,
            );
        }

        $service = $response->json('data');

        if (! is_array($service) || ! isset($service['id'], $service['ministere_id'])) {
            throw new PatrimoineServiceException('La réponse du service patrimoine est invalide.', 502);
        }

        return $service;
    }

    public function acquisition(int $acquisitionId, string $token): ?array
    {
        $response = $this->send('GET', '/api/acquisitions/'.$acquisitionId, $token);

        if ($response->notFound()) {
            return null;
        }

        if (! $response->successful()) {
            throw new PatrimoineServiceException(
                'L’acquisition n’a pas pu être vérifiée auprès du service patrimoine.',
                $response->serverError() ? 503 : 502,
            );
        }

        $acquisition = $response->json('data');

        if (! is_array($acquisition) || ! isset($acquisition['id'])) {
            throw new PatrimoineServiceException('La réponse du service patrimoine est invalide.', 502);
        }

        return $acquisition;
    }

    public function changerService(int $bienId, int $serviceId, string $token): void
    {
        $response = $this->send('PUT', '/api/biens/'.$bienId, $token, [
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
