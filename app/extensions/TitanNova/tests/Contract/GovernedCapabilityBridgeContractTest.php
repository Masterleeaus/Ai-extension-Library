<?php

declare(strict_types=1);

use App\Domains\WorkCore\System\Actions\ActionDefinition;
use App\Domains\WorkCore\System\Actions\BusinessActionRegistry;
use App\Domains\WorkCore\System\Capabilities\CapabilityDefinition;
use App\Domains\WorkCore\System\Capabilities\CapabilityRegistry;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;
use App\Extensions\TitanNova\System\Capabilities\Contracts\GovernedExecutionContract;
use App\Extensions\TitanNova\System\Capabilities\Contracts\NativeToolMappingContract;
use App\Extensions\TitanNova\System\Capabilities\GovernedWorkCoreCapabilityBridge;

$root = dirname(__DIR__, 5);

require_once $root.'/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/ActionDefinition.php';
require_once $root.'/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/BusinessActionRegistry.php';
require_once $root.'/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Capabilities/CapabilityDefinition.php';
require_once $root.'/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Capabilities/CapabilityRegistry.php';
require_once $root.'/app/extensions/Chatbot/System/TitanAI/governance/System/Tools/GovernedToolDefinition.php';
require_once $root.'/app/extensions/TitanNova/System/Capabilities/Contracts/GovernedExecutionContract.php';
require_once $root.'/app/extensions/TitanNova/System/Capabilities/Contracts/NativeToolMappingContract.php';
require_once $root.'/app/extensions/TitanNova/System/Capabilities/GovernedCapabilityPlan.php';
require_once $root.'/app/extensions/TitanNova/System/Capabilities/GovernedWorkCoreCapabilityBridge.php';

final class FakeNativeToolMapping implements NativeToolMappingContract
{
    /** @param array<string,bool> $mapped */
    public function __construct(private array $mapped = []) {}

    public function has(string $domain, string $operation): bool
    {
        return $this->mapped[$domain.'.'.$operation] ?? false;
    }
}

final class FakeGovernedExecution implements GovernedExecutionContract
{
    public ?GovernedToolDefinition $definition = null;
    public array $payload = [];
    public array $context = [];

    /** @param array<string,mixed> $result */
    public function __construct(private array $result = [
        'id' => 101,
        '_action_receipt' => ['receiptId' => 'receipt-101', 'status' => 'executed'],
    ]) {}

    public function execute(GovernedToolDefinition $definition, array $payload, array $context): array
    {
        $this->definition = $definition;
        $this->payload = $payload;
        $this->context = $context;

        return $this->result;
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.' Expected '.var_export($expected, true).' got '.var_export($actual, true));
    }
}

function expectRuntimeException(callable $callback, string $messageContains): void
{
    try {
        $callback();
    } catch (RuntimeException $exception) {
        assertTrue(str_contains($exception->getMessage(), $messageContains), 'Unexpected exception: '.$exception->getMessage());
        return;
    }

    throw new RuntimeException('Expected RuntimeException containing: '.$messageContains);
}

function bridgeWith(
    BusinessActionRegistry $actions,
    CapabilityRegistry $capabilities,
    NativeToolMappingContract $mappings,
    GovernedExecutionContract $execution,
): GovernedWorkCoreCapabilityBridge {
    return new GovernedWorkCoreCapabilityBridge($actions, $capabilities, $mappings, $execution);
}

$emptyActions = new BusinessActionRegistry();
$emptyCapabilities = new CapabilityRegistry();
$execution = new FakeGovernedExecution();
$bridge = bridgeWith($emptyActions, $emptyCapabilities, new FakeNativeToolMapping(), $execution);
expectRuntimeException(fn () => $bridge->planFor('crm.create_contact'), 'not registered');

$actionsWithoutCapability = new BusinessActionRegistry();
$actionsWithoutCapability->register(new ActionDefinition(
    key: 'crm.create_contact',
    handler: stdClass::class,
    risk: 'low',
    capability: 'workcore.crm',
    permission: 'customers.create',
));
$bridge = bridgeWith($actionsWithoutCapability, new CapabilityRegistry(), new FakeNativeToolMapping(['crm.create_contact' => true]), $execution);
expectRuntimeException(fn () => $bridge->planFor('crm.create_contact'), 'capability [workcore.crm]');

$capabilities = new CapabilityRegistry();
$capabilities->register(new CapabilityDefinition('workcore.crm', 'WorkCore', '1.0.0'));
$bridge = bridgeWith($actionsWithoutCapability, $capabilities, new FakeNativeToolMapping(), $execution);
expectRuntimeException(fn () => $bridge->planFor('crm.create_contact'), 'native WorkCore tool mapping');

$actions = new BusinessActionRegistry();
$actions->register(new ActionDefinition(
    key: 'crm.create_contact',
    handler: stdClass::class,
    risk: 'medium',
    requiresConfirmation: true,
    capability: 'workcore.crm',
    permission: 'customers.create',
    metadata: ['rollback_supported' => true, 'timeout_seconds' => 45],
));
$mapping = new FakeNativeToolMapping(['crm.create_contact' => true]);
$execution = new FakeGovernedExecution();
$bridge = bridgeWith($actions, $capabilities, $mapping, $execution);
$plan = $bridge->planFor('crm.create_contact');

assertSameValue('crm.create_contact', $plan->actionKey, 'Plan action key mismatch.');
assertSameValue('workcore.crm', $plan->capability, 'Plan capability mismatch.');
assertTrue($plan->requiresConfirmation, 'Confirmation requirement was not preserved.');
assertSameValue('crm', $plan->definition->domain, 'Governed domain mismatch.');
assertSameValue('create_contact', $plan->definition->operation, 'Governed operation mismatch.');
assertSameValue('medium', $plan->definition->riskLevel, 'Risk level mismatch.');
assertSameValue(['customers.create'], $plan->definition->permissions, 'Permission mismatch.');
assertTrue($plan->definition->rollbackSupported, 'Rollback support mismatch.');
assertSameValue(45, $plan->definition->timeoutSeconds, 'Timeout mismatch.');

expectRuntimeException(
    fn () => $bridge->execute('crm.create_contact', ['name' => 'Ada'], []),
    'tenant, actor and idempotency',
);

$result = $bridge->execute(
    'crm.create_contact',
    ['name' => 'Ada'],
    [
        'tenant_id' => 7,
        'user_id' => 9,
        'idempotency_key' => 'nova-mission-7-9-1',
        'constraints' => ['source' => 'titan-nova'],
    ],
);

assertSameValue('receipt-101', $result['_action_receipt']['receiptId'] ?? null, 'Receipt was not returned.');
assertSameValue('crm.create_contact', $execution->definition?->domain.'.'.$execution->definition?->operation, 'Execution definition mismatch.');
assertSameValue('workcore.crm', $execution->context['constraints']['workcore_capability'] ?? null, 'Capability constraint missing.');
assertSameValue(true, $execution->context['constraints']['workcore_confirmation_required'] ?? null, 'Confirmation constraint missing.');
assertSameValue('nova-mission-7-9-1', $execution->context['idempotency_key'] ?? null, 'Idempotency context missing.');

$receiptless = bridgeWith(
    $actions,
    $capabilities,
    $mapping,
    new FakeGovernedExecution(['id' => 202]),
);
expectRuntimeException(
    fn () => $receiptless->execute('crm.create_contact', ['name' => 'Grace'], [
        'tenant_id' => 7,
        'user_id' => 9,
        'idempotency_key' => 'nova-mission-7-9-2',
    ]),
    'without an action receipt',
);

echo "Governed capability bridge contract tests passed.\n";
