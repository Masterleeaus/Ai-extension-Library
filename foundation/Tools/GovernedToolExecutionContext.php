<?php

declare(strict_types=1);

namespace Foundation\Tools;

/**
 * Context for governed tool execution.
 *
 * Tracks all information needed for a tool execution including
 * tenant context, actor identity, and idempotency state.
 */
readonly class GovernedToolExecutionContext
{
    public function __construct(
        public string $tenantId,
        public string $userId,
        public string $actorId,
        public string $toolName,
        public array $parameters,
        public ?string $idempotencyKey = null,
        public array $context = [],
    ) {
    }

    /**
     * Validate that tenant and actor context are present.
     *
     * @throws \InvalidArgumentException if context is missing
     */
    public function validateContext(): void
    {
        if (empty($this->tenantId)) {
            throw new \InvalidArgumentException('Tenant ID is required');
        }

        if (empty($this->userId)) {
            throw new \InvalidArgumentException('User ID is required');
        }

        if (empty($this->actorId)) {
            throw new \InvalidArgumentException('Actor ID is required');
        }
    }

    /**
     * Get a correlation ID for this execution.
     *
     * Used for tracing and auditing.
     */
    public function getCorrelationId(): string
    {
        return "{$this->tenantId}:{$this->userId}:{$this->toolName}:" . md5(json_encode([
            'parameters' => $this->parameters,
            'time' => time(),
        ]));
    }
}
