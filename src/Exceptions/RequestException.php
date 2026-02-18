<?php

declare(strict_types=1);

namespace Datomatic\CartaDelDocente\Exceptions;

use RuntimeException;

class RequestException extends RuntimeException
{
    public function __construct(string $code, string $message)
    {
        parent::__construct($message, $code);
    }
}
