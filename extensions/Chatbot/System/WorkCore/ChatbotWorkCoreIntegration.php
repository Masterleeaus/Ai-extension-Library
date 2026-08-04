<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\WorkCore;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Authorization\Policies\ConnectorAuthorizationPolicyContract;

final class ChatbotWorkCoreIntegration
{
    public function __construct(
        private TenantContextContract $tenantContext,
        private OperationContextContract $operationContext,
        private ConnectorAuthorizationPolicyContract $connectorPolicy,
    ) {}

    public function resolveConversationContext(int $conversationId): array
    {
        return [
            'tenantId' => $this->tenantContext->companyId(),
            'userId' => $this->tenantContext->userId(),
            'actorId' => $this->operationContext->actorId(),
            'correlationId' => $this->operationContext->correlationId(),
            'conversationId' => $conversationId,
            'locale' => $this->operationContext->locale(),
        ];
    }

    public function canAccessConversation(int $conversationId): bool
    {
        return $this->operationContext->hasContext() &&
               $this->operationContext->actorId() !== null;
    }

    public function canUseConnector(string $connectorName, array $config = []): bool
    {
        return $this->connectorPolicy->canUseConnector(
            $connectorName,
            $this->operationContext,
            $config
        );
    }

    public function canIngestKnowledge(string $sourceType): bool
    {
        return $this->operationContext->hasContext();
    }

    public function getDenialReasonForConnector(string $connectorName): ?string
    {
        return $this->connectorPolicy->getDenialReasonForConnector(
            $connectorName,
            $this->operationContext
        );
    }

    public function getWorkCoreContext(): array
    {
        return [
            'tenantId' => $this->tenantContext->companyId(),
            'userId' => $this->tenantContext->userId(),
            'actorId' => $this->operationContext->actorId(),
            'locale' => $this->operationContext->locale(),
            'timezone' => $this->operationContext->timezone(),
            'correlationId' => $this->operationContext->correlationId(),
            'offlineCapable' => true, // PWA feature
        ];
    }

    public function canGoOffline(): bool
    {
        return true; // PWA always supports offline mode
    }
}
