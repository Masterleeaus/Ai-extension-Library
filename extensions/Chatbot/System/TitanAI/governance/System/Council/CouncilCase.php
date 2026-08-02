<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Council;

use App\Extensions\TitanAIGovernance\System\Enums\RiskLevel;

final readonly class CouncilCase
{
    public function __construct(
        public string $action,
        public array $proposedPayload,
        public array $facts,
        public array $constraints,
        public RiskLevel $risk,
        public string $tenantId,
        public string $userId,
        public string $deviceId,
        public ?string $idempotencyKey = null,
    ) {}

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'proposed_payload' => $this->proposedPayload,
            'facts' => $this->facts,
            'constraints' => $this->constraints,
            'risk_level' => $this->risk->value,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}
