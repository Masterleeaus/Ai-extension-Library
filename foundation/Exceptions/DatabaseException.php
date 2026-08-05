<?php

declare(strict_types=1);

namespace Foundation\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Custom exception for database operation failures.
 *
 * Wraps PDO errors with context about the failed operation,
 * sanitized parameters, and retry-ability information.
 */
class DatabaseException extends RuntimeException
{
    private ?string $sqlState = null;
    private bool $isTransient = false;
    private ?array $context = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $sqlState = null,
        bool $isTransient = false,
        ?array $context = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->sqlState = $sqlState;
        $this->isTransient = $isTransient;
        $this->context = $context;
    }

    /**
     * Get the SQL state code for this error.
     */
    public function getSqlState(): ?string
    {
        return $this->sqlState;
    }

    /**
     * Whether this error is transient (might succeed on retry).
     */
    public function isTransient(): bool
    {
        return $this->isTransient;
    }

    /**
     * Get contextual information about the failed operation.
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Check if error is a known transient SQL state.
     *
     * Transient errors include:
     * - 08006: Connection failure
     * - 08S01: Communication link failure
     * - 40001: Serialization failure
     * - 40P01: Deadlock detected
     * - 57P03: Cannot execute queries
     */
    public static function isTransientSqlState(?string $sqlState): bool
    {
        $transientStates = ['08006', '08S01', '40001', '40P01', '57P03'];
        return in_array($sqlState, $transientStates, true);
    }
}
