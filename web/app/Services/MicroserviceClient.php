<?php

namespace App\Services;

use App\Exceptions\ServiceAuthenticationException;
use App\Exceptions\ServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MicroserviceClient
{
    public function request(string $service, string $method, string $path, array $options = []): Response
    {
        $baseUrl = config("services.{$service}.url");

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new ServiceUnavailableException($service);
        }

        try {
            $response = Http::acceptJson()
                ->withToken((string) session('jwt_token'))
                ->timeout(5)
                ->send($method, rtrim($baseUrl, '/').'/'.ltrim($path, '/'), $options);
        } catch (ConnectionException $exception) {
            throw new ServiceUnavailableException($service, $exception);
        }

        if ($response->unauthorized()) {
            throw new ServiceAuthenticationException("Le jeton transmis à {$service} a été refusé.");
        }

        if ($response->serverError()) {
            throw new ServiceUnavailableException($service);
        }

        return $response;
    }
}
