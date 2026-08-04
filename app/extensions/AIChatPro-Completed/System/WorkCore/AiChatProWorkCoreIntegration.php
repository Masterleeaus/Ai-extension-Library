<?php

declare(strict_types=1);

namespace App\Extensions\AIChatPro\System\WorkCore;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Authorization\Policies\ToolAuthorizationPolicyContract;

final class AiChatProWorkCoreIntegration
{
    public function __construct(
        private TenantContextContract $tenantContext,
        private OperationContextContract $operationContext,
        private ToolAuthorizationPolicyContract $toolPolicy,
    ) {}

    public function resolveConversationContext(int $conversationId): array
    {
        return [
            'tenantId' => $this->tenantContext->companyId(),
            'userId' => $this->tenantContext->userId(),
            'actorId' => $this->operationContext->actorId(),
            'correlationId' => $this->operationContext->correlationId(),
            'conversationId' => $conversationId,
        ];
    }

    public function canAccessConversation(int $conversationId): bool
    {
        return $this->operationContext->hasContext() &&
               $this->operationContext->actorId() !== null;
    }

    public function canExecuteSkill(string $skillName, array $parameters = []): bool
    {
        return $this->toolPolicy->canExecuteTool($skillName, $this->operationContext, $parameters);
    }

    public function canAccessFileChat(int $folderId): bool
    {
        return $this->operationContext->hasContext();
    }

    public function getDenialReasonForSkill(string $skillName): ?string
    {
        return $this->toolPolicy->getDenialReason($skillName, $this->operationContext);
    }

    public function getWorkCoreContext(): array
    {
        return [
            'tenantId' => $this->tenantContext->companyId(),
            'userId' => $this->tenantContext->userId(),
            'actorId' => $this->operationContext->actorId(),
            'workerId' => $this->operationContext->workerId(),
            'branchId' => $this->operationContext->branchId(),
            'locale' => $this->operationContext->locale(),
            'timezone' => $this->operationContext->timezone(),
            'correlationId' => $this->operationContext->correlationId(),
            'causationId' => $this->operationContext->causationId(),
        ];
    }
}
