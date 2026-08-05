<?php

declare(strict_types=1);

namespace Foundation\Support;

use Exception;
use Throwable;

class TransactionException extends Exception
{
    private ?Throwable $previousException = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->previousException = $previous;
    }

    public function getPreviousException(): ?Throwable
    {
        return $this->previousException;
    }
}
