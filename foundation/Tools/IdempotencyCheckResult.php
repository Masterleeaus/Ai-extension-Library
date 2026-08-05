<?php

declare(strict_types=1);

namespace Foundation\Tools;

/**
 * Result of idempotency check.
 *
 * Indicates whether a request is new or a duplicate of a previous execution.
 */
readonly class IdempotencyCheckResult
{
    public function __construct(
        private bool $isDuplicate = false,
        private bool $isIdempotent = false,
        private ?GovernedToolExecutionResult $result = null,
    ) {
    }

    /**
     * Request is not idempotent (process every time).
     */
    public static function notIdempotent(): self
    {
        return new self(isDuplicate: false, isIdempotent: false);
    }

    /**
     * Request is idempotent but not a duplicate (first time).
     */
    public static function notDuplicate(): self
    {
        return new self(isDuplicate: false, isIdempotent: true);
    }

    /**
     * Request is a duplicate of a previous execution.
     */
    public static function duplicate(GovernedToolExecutionResult $result): self
    {
        return new self(isDuplicate: true, isIdempotent: true, result: $result);
    }

    /**
     * Check if this is a duplicate request.
     */
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }

    /**
     * Get the result of the previous execution (if duplicate).
     */
    public function getResult(): ?GovernedToolExecutionResult
    {
        return $this->result;
    }
}
