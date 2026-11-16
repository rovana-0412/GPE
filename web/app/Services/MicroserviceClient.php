<?php

namespace App\Services;

use App\Exceptions\ServiceAuthenticationException;
use App\Exceptions\ServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class MicroserviceClient
{
    public function request(string $service, string $method, string $path, array $options = []): Response
    {
        $baseUrl = config("services.{$service}.url");
        $timeout = (int) ($options['_timeout'] ?? 5);
        unset($options['_timeout']);

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new ServiceUnavailableException($service);
        }

        try {
            $response = Http::acceptJson()
                ->withToken((string) session('jwt_token'))
                ->timeout($timeout)
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

    public function upload(
        string $service,
        string $path,
        UploadedFile $file,
        array $fields = [],
    ): Response {
        $baseUrl = config("services.{$service}.url");

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new ServiceUnavailableException($service);
        }

        try {
            $response = Http::acceptJson()
                ->withToken((string) session('jwt_token'))
                ->timeout(20)
                ->attach('fichier', $file->get(), $file->getClientOriginalName())
                ->post(rtrim($baseUrl, '/').'/'.ltrim($path, '/'), $fields);
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
