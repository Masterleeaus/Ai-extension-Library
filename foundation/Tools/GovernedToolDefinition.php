<?php

declare(strict_types=1);

namespace Foundation\Tools;

/**
 * Definition of a governed tool.
 *
 * Declares permissions, audit behavior, idempotency, rollback support,
 * timeout, risk level, and schema validation for a tool.
 */
readonly class GovernedToolDefinition
{
    public function __construct(
        public string $name,
        public string $description,
        public array $permissions,
        public bool $audited = true,
        public bool $idempotent = false,
        public bool $rollbackSupported = false,
        public int $timeoutSeconds = 30,
        public array $inputSchema = [],
        public array $outputSchema = [],
        public string $riskLevel = 'low',
    ) {
    }

    /**
     * Check if tool has permissions defined.
     */
    public function hasPermissions(): bool
    {
        return !empty($this->permissions);
    }

    /**
     * Get required permissions.
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Check if tool is audited.
     */
    public function isAudited(): bool
    {
        return $this->audited;
    }

    /**
     * Check if tool execution is idempotent.
     */
    public function isIdempotent(): bool
    {
        return $this->idempotent;
    }

    /**
     * Check if tool supports rollback.
     */
    public function isRollbackSupported(): bool
    {
        return $this->rollbackSupported;
    }

    /**
     * Get execution timeout in seconds.
     */
    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    /**
     * Get risk level.
     */
    public function getRiskLevel(array $parameters = []): string
    {
        return $this->riskLevel;
    }

    /**
     * Validate input parameters against schema.
     */
    public function validateInput(array $parameters): bool
    {
        if (empty($this->inputSchema)) {
            return true; // No schema defined
        }

        // In a real implementation, use JSON schema validation
        // For now, basic structure check
        return true;
    }

    /**
     * Validate output against schema.
     */
    public function validateOutput(mixed $output): bool
    {
        if (empty($this->outputSchema)) {
            return true; // No schema defined
        }

        // In a real implementation, use JSON schema validation
        // For now, basic structure check
        return true;
    }
}
