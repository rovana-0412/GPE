<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ServiceUnavailableException extends RuntimeException
{
    public function __construct(public readonly string $service, ?Throwable $previous = null)
    {
        parent::__construct('Un microservice est indisponible.', 0, $previous);
    }
}
