<?php

namespace App\Domains\Shared\Authorization;

use App\Domains\Shared\Context\TenantContext;

interface AuthorizationPolicy
{
    /**
     * Check if context is authorized for an action
     */
    public function isAuthorized(TenantContext $context, string $action): bool;

    /**
     * Get authorization reason (for logging/audit)
     */
    public function getAuthorizationReason(): string;
}

interface ToolAuthorizationPolicy extends AuthorizationPolicy
{
    /**
     * Can user execute a specific tool
     */
    public function canExecuteTool(TenantContext $context, string $toolName): bool;

    /**
     * Get execution limits for this tool
     */
    public function getToolExecutionLimits(TenantContext $context, string $toolName): ToolExecutionLimits;
}

interface WorkflowAuthorizationPolicy extends AuthorizationPolicy
{
    /**
     * Can user execute a workflow action
     */
    public function canExecuteWorkflowAction(TenantContext $context, string $actionType): bool;

    /**
     * Get execution constraints for this action type
     */
    public function getActionConstraints(TenantContext $context, string $actionType): ActionConstraints;
}

interface ConnectorAuthorizationPolicy extends AuthorizationPolicy
{
    /**
     * Can user use a specific connector
     */
    public function canUseConnector(TenantContext $context, string $connectorName): bool;

    /**
     * Get connector operation limits
     */
    public function getConnectorLimits(TenantContext $context, string $connectorName): ConnectorLimits;
}

interface KnowledgeAuthorizationPolicy extends AuthorizationPolicy
{
    /**
     * Can user ingest knowledge from a source
     */
    public function canIngestKnowledge(TenantContext $context, string $sourceType): bool;

    /**
     * Get knowledge ingestion limits
     */
    public function getKnowledgeLimits(TenantContext $context, string $sourceType): KnowledgeLimits;
}

class ToolExecutionLimits
{
    public function __construct(
        public int $maxCallsPerHour = 1000,
        public int $maxConcurrentCalls = 10,
        public int $maxTimeoutSeconds = 300,
        public float $maxCostUsd = 100.00,
    ) {}
}

class ActionConstraints
{
    public function __construct(
        public int $maxExecutionsPerDay = 10000,
        public int $maxRetries = 3,
        public bool $requiresApproval = false,
        public array $allowedVerticals = ['*'],
    ) {}
}

class ConnectorLimits
{
    public function __construct(
        public int $messagesPerMinute = 60,
        public int $messagesPerDay = 10000,
        public bool $canSendMedia = true,
        public array $allowedMediaTypes = ['text', 'image', 'document'],
    ) {}
}

class KnowledgeLimits
{
    public function __construct(
        public int $maxDocumentsPerDay = 100,
        public int $maxFileSizeMb = 10,
        public int $maxTotalSizeMb = 1000,
        public bool $canIngestImages = true,
    ) {}
}
