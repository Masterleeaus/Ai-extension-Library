<?php

namespace App\Domains\Shared\Authorization;

use App\Domains\Shared\Context\TenantContext;
use App\Domains\Shared\Identity\ActorIdentity;

class DefaultAuthorizationPolicy implements
    ToolAuthorizationPolicy,
    WorkflowAuthorizationPolicy,
    ConnectorAuthorizationPolicy,
    KnowledgeAuthorizationPolicy
{
    private string $lastReason = '';

    public function isAuthorized(TenantContext $context, string $action): bool
    {
        // Check basic permissions
        if (!$context->hasPermission($action)) {
            $this->lastReason = "Permission '$action' not granted";
            return false;
        }

        $this->lastReason = "Permission '$action' granted";
        return true;
    }

    public function getAuthorizationReason(): string
    {
        return $this->lastReason;
    }

    public function canExecuteTool(TenantContext $context, string $toolName): bool
    {
        return $this->isAuthorized($context, "tool:execute:$toolName");
    }

    public function getToolExecutionLimits(TenantContext $context, string $toolName): ToolExecutionLimits
    {
        // Check if user has higher limits based on permissions
        if ($context->hasPermission('tools:premium')) {
            return new ToolExecutionLimits(
                maxCallsPerHour: 5000,
                maxConcurrentCalls: 50,
                maxTimeoutSeconds: 600,
                maxCostUsd: 500.00
            );
        }

        // Check if user has enterprise limits
        if ($context->hasPermission('tools:enterprise')) {
            return new ToolExecutionLimits(
                maxCallsPerHour: 100000,
                maxConcurrentCalls: 500,
                maxTimeoutSeconds: 3600,
                maxCostUsd: 5000.00
            );
        }

        // Default limits
        return new ToolExecutionLimits();
    }

    public function canExecuteWorkflowAction(TenantContext $context, string $actionType): bool
    {
        return $this->isAuthorized($context, "workflow:action:$actionType");
    }

    public function getActionConstraints(TenantContext $context, string $actionType): ActionConstraints
    {
        // Check if action requires approval
        $requiresApproval = in_array($actionType, [
            'create_invoice',
            'send_payment',
            'modify_customer',
            'delete_record'
        ]);

        if ($context->hasPermission('workflows:bypass-approval')) {
            $requiresApproval = false;
        }

        // Check allowed verticals
        $allowedVerticals = $context->hasPermission('workflows:all-verticals')
            ? ['*']
            : ($context->getPermissions() ?? []);

        return new ActionConstraints(
            maxExecutionsPerDay: $context->hasPermission('workflows:enterprise') ? 100000 : 10000,
            maxRetries: $context->hasPermission('workflows:premium') ? 5 : 3,
            requiresApproval: $requiresApproval,
            allowedVerticals: $allowedVerticals
        );
    }

    public function canUseConnector(TenantContext $context, string $connectorName): bool
    {
        return $this->isAuthorized($context, "connector:use:$connectorName");
    }

    public function getConnectorLimits(TenantContext $context, string $connectorName): ConnectorLimits
    {
        if ($context->hasPermission('connectors:enterprise')) {
            return new ConnectorLimits(
                messagesPerMinute: 1000,
                messagesPerDay: 1000000,
                canSendMedia: true,
                allowedMediaTypes: ['text', 'image', 'document', 'video', 'audio']
            );
        }

        if ($context->hasPermission('connectors:premium')) {
            return new ConnectorLimits(
                messagesPerMinute: 300,
                messagesPerDay: 100000,
                canSendMedia: true,
                allowedMediaTypes: ['text', 'image', 'document']
            );
        }

        return new ConnectorLimits();
    }

    public function canIngestKnowledge(TenantContext $context, string $sourceType): bool
    {
        return $this->isAuthorized($context, "knowledge:ingest:$sourceType");
    }

    public function getKnowledgeLimits(TenantContext $context, string $sourceType): KnowledgeLimits
    {
        if ($context->hasPermission('knowledge:enterprise')) {
            return new KnowledgeLimits(
                maxDocumentsPerDay: 10000,
                maxFileSizeMb: 100,
                maxTotalSizeMb: 100000,
                canIngestImages: true
            );
        }

        return new KnowledgeLimits();
    }
}
