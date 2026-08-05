<?php

declare(strict_types=1);

namespace App\Extensions\TitanNova\System\Capabilities;

use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;

final readonly class GovernedCapabilityPlan
{
    public function __construct(
        public string $actionKey,
        public string $capability,
        public bool $requiresConfirmation,
        public GovernedToolDefinition $definition,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'action_key' => $this->actionKey,
            'capability' => $this->capability,
            'requires_confirmation' => $this->requiresConfirmation,
            'governed_tool' => [
                'name' => $this->definition->name,
                'domain' => $this->definition->domain,
                'operation' => $this->definition->operation,
                'risk_level' => $this->definition->riskLevel,
                'permissions' => $this->definition->permissions,
                'audited' => $this->definition->audited,
                'idempotent' => $this->definition->idempotent,
                'rollback_supported' => $this->definition->rollbackSupported,
                'timeout_seconds' => $this->definition->timeoutSeconds,
            ],
        ];
    }
}
