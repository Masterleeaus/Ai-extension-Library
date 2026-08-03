<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\DTO;

use InvalidArgumentException;

final class TitanAIRequest
{
    /** @param array<int,array<string,mixed>> $messages @param array<string,mixed> $payload @param array<int,array<string,mixed>> $attachments @param array<int,string> $toolPermissions */
    public function __construct(
        public readonly string $message,
        public readonly string $tenantId,
        public readonly string $userId,
        public readonly string $deviceId,
        public readonly string $channel,
        public readonly string $conversationSource,
        public readonly ?string $conversationId = null,
        public readonly ?string $assistantDeploymentId = null,
        public readonly ?string $template = null,
        public readonly ?string $workflowId = null,
        public readonly ?string $idempotencyKey = null,
        public readonly string $responseMode = 'json',
        public readonly array $messages = [],
        public readonly array $payload = [],
        public readonly array $attachments = [],
        public readonly array $toolPermissions = [],
        public readonly array $availablePermissions = [],
    ) {
        foreach (['message' => $message, 'tenantId' => $tenantId, 'userId' => $userId, 'deviceId' => $deviceId, 'channel' => $channel] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException("Titan AI request field [{$field}] is required.");
            }
        }
    }

    /** @return array<string,mixed> */
    public function context(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'conversation_id' => $this->conversationId,
            'conversation_source' => $this->conversationSource,
            'assistant_deployment_id' => $this->assistantDeploymentId,
            'channel' => $this->channel,
            'template' => $this->template,
            'workflow_id' => $this->workflowId,
            'idempotency_key' => $this->idempotencyKey,
            'tool_permissions' => $this->toolPermissions,
            'available_permissions' => $this->availablePermissions,
            'response_mode' => $this->responseMode,
        ];
    }
}
