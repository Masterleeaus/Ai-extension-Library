<?php

declare(strict_types=1);

namespace App\Extensions\TitanNova\System\Capabilities;

use App\Domains\WorkCore\System\Actions\ActionDefinition;
use App\Domains\WorkCore\System\Actions\BusinessActionRegistry;
use App\Domains\WorkCore\System\Capabilities\CapabilityRegistry;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;
use App\Extensions\TitanNova\System\Capabilities\Contracts\GovernedExecutionContract;
use App\Extensions\TitanNova\System\Capabilities\Contracts\NativeToolMappingContract;
use RuntimeException;

final class GovernedWorkCoreCapabilityBridge
{
    public function __construct(
        private BusinessActionRegistry $actions,
        private CapabilityRegistry $capabilities,
        private NativeToolMappingContract $mappings,
        private GovernedExecutionContract $execution,
    ) {}

    public function planFor(string $actionKey): GovernedCapabilityPlan
    {
        $actionKey = trim($actionKey);
        $action = $this->actions->get($actionKey);
        if (! $action instanceof ActionDefinition) {
            throw new RuntimeException("WorkCore action [{$actionKey}] is not registered.");
        }

        $capability = trim((string) $action->capability);
        if ($capability === '' || ! $this->capabilities->has($capability)) {
            $display = $capability === '' ? 'undefined' : $capability;
            throw new RuntimeException("WorkCore capability [{$display}] is not registered for action [{$actionKey}].");
        }

        [$domain, $operation] = $this->splitActionKey($actionKey);
        if (! $this->mappings->has($domain, $operation)) {
            throw new RuntimeException("No native WorkCore tool mapping exists for [{$actionKey}].");
        }

        $permission = trim((string) $action->permission);
        $timeout = max(1, (int) ($action->metadata['timeout_seconds'] ?? 30));

        return new GovernedCapabilityPlan(
            actionKey: $actionKey,
            capability: $capability,
            requiresConfirmation: $action->requiresConfirmation,
            definition: new GovernedToolDefinition(
                name: 'titan-nova.'.$actionKey,
                domain: $domain,
                operation: $operation,
                riskLevel: $action->risk,
                permissions: $permission === '' ? [] : [$permission],
                audited: true,
                idempotent: true,
                rollbackSupported: (bool) ($action->metadata['rollback_supported'] ?? false),
                timeoutSeconds: $timeout,
            ),
        );
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $context @return array<string,mixed> */
    public function execute(string $actionKey, array $payload, array $context): array
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $actorId = (int) ($context['user_id'] ?? 0);
        $idempotencyKey = trim((string) ($context['idempotency_key'] ?? ''));
        if ($tenantId < 1 || $actorId < 1 || $idempotencyKey === '') {
            throw new RuntimeException('Governed WorkCore execution requires tenant, actor and idempotency context.');
        }

        $plan = $this->planFor($actionKey);
        $constraints = (array) ($context['constraints'] ?? []);
        $context['constraints'] = [
            ...$constraints,
            'workcore_action' => $plan->actionKey,
            'workcore_capability' => $plan->capability,
            'workcore_confirmation_required' => $plan->requiresConfirmation,
        ];
        $context['tenant_id'] = $tenantId;
        $context['user_id'] = $actorId;
        $context['idempotency_key'] = $idempotencyKey;

        $result = $this->execution->execute($plan->definition, $payload, $context);
        if (! isset($result['_action_receipt']) || ! is_array($result['_action_receipt'])) {
            throw new RuntimeException('Governed WorkCore execution returned without an action receipt.');
        }

        return $result;
    }

    /** @return array{0:string,1:string} */
    private function splitActionKey(string $actionKey): array
    {
        $segments = explode('.', $actionKey, 2);
        if (count($segments) !== 2 || trim($segments[0]) === '' || trim($segments[1]) === '') {
            throw new RuntimeException("WorkCore action [{$actionKey}] must use domain.operation format.");
        }

        return [trim($segments[0]), trim($segments[1])];
    }
}
