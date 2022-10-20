<?php

declare(strict_types=1);

namespace Datomatic\CartaDelDocente\Exceptions;

use RuntimeException;

class RequestException extends RuntimeException
{
    public function __construct(string $code, string $message)
    {
        // Matches the error message of invalid Foo::BAR access
        parent::__construct("RequestException: [code $code] $message");
    }
}
