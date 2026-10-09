<?php

namespace App\Exceptions;

use Exception;

/**
 * A business-rule violation (quota exceeded, cooldown active, blocked, …).
 * Rendered as HTTP 422 by the API exception handler.
 */
class DomainException extends Exception
{
    public function __construct(string $message, public int $status = 422)
    {
        parent::__construct($message);
    }
}
