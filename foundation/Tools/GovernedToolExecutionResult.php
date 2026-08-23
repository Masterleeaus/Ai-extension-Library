<?php

declare(strict_types=1);

namespace Foundation\Tools;

/**
 * Result of governed tool execution.
 *
 * Encapsulates execution outcome including status, result, and audit receipt.
 */
readonly class GovernedToolExecutionResult
{
    public function __construct(
        public string $status, // success, denied, failed, error, timeout, cancelled
        public mixed $result = null,
        public string $correlationId = '',
        public array $receipt = [],
        public ?string $reason = null,
        public bool $isRetryable = false,
    ) {
    }

    /**
     * Create a successful execution result.
     */
    public static function success(
        mixed $result,
        string $correlationId,
        array $receipt
    ): self {
        return new self(
            status: 'success',
            result: $result,
            correlationId: $correlationId,
            receipt: $receipt,
        );
    }

    /**
     * Create a denied execution result (permission denied).
     */
    public static function denied(string $reason, string $correlationId): self
    {
        return new self(
            status: 'denied',
            correlationId: $correlationId,
            reason: $reason,
        );
    }

    /**
     * Create a failed execution result.
     */
    public static function failed(
        string $reason,
        string $correlationId,
        bool $isRetryable = false
    ): self {
        return new self(
            status: 'failed',
            correlationId: $correlationId,
            reason: $reason,
            isRetryable: $isRetryable,
        );
    }

    /**
     * Create an error result.
     */
    public static function error(string $reason, string $correlationId): self
    {
        return new self(
            status: 'error',
            correlationId: $correlationId,
            reason: $reason,
        );
    }

    /**
     * Create a timeout result.
     */
    public static function timeout(string $correlationId): self
    {
        return new self(
            status: 'timeout',
            correlationId: $correlationId,
            reason: 'Tool execution exceeded timeout',
            isRetryable: true,
        );
    }

    /**
     * Check if execution was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if execution can be retried.
     */
    public function canRetry(): bool
    {
        return $this->isRetryable;
    }
}
