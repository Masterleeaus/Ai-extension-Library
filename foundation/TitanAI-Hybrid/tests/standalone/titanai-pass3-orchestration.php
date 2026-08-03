<?php

declare(strict_types=1);

namespace Illuminate\Support {
    final class Collection implements \Countable
    {
        public function __construct(private array $items = []) {}
        public function put(string $key, mixed $value): self { $this->items[$key] = $value; return $this; }
        public function get(string $key): mixed { return $this->items[$key] ?? null; }
        public function has(string $key): bool { return array_key_exists($key, $this->items); }
        public function keys(): self { return new self(array_keys($this->items)); }
        public function values(): self { return new self(array_values($this->items)); }
        public function all(): array { return $this->items; }
        public function count(): int { return count($this->items); }
        public function mapWithKeys(callable $callback): self
        {
            $mapped = [];
            foreach ($this->items as $key => $value) $mapped += $callback($value, $key);
            return new self($mapped);
        }
    }
}

namespace Illuminate\Foundation\Events { trait Dispatchable {} }
namespace Illuminate\Queue { trait SerializesModels {} }

namespace Tests\TitanAI\Pass3\Fakes {
    final class Database
    {
        /** @var array<string,list<array<string,mixed>>> */
        public static array $tables = [];
        public static bool $throwOnRead = false;
        public static function reset(): void { self::$tables = []; self::$throwOnRead = false; }
    }

    final class Query
    {
        /** @var array<string,mixed> */
        private array $where = [];
        private ?string $orderBy = null;
        public function __construct(private readonly string $table) {}
        public function where(array|string $column, mixed $value = null): self
        {
            if (is_array($column)) $this->where += $column; else $this->where[$column] = $value;
            return $this;
        }
        public function whereNotNull(string $column): self { $this->where[$column] = '__NOT_NULL__'; return $this; }
        public function orderBy(string $column): self { $this->orderBy = $column; return $this; }
        /** @param list<array<string,mixed>> $values @param list<string> $uniqueBy @param list<string> $update */
        public function upsert(array $values, array $uniqueBy, array $update): int
        {
            $rows =& Database::$tables[$this->table]; $rows ??= [];
            foreach ($values as $candidate) {
                $identity = array_intersect_key($candidate, array_flip($uniqueBy));
                foreach ($rows as &$row) {
                    if ($this->matches($row, $identity)) {
                        foreach ($update as $column) $row[$column] = $candidate[$column] ?? null;
                        continue 2;
                    }
                }
                unset($row);
                $rows[] = ['id' => count($rows) + 1] + $candidate;
            }
            return count($values);
        }
        public function first(): ?object
        {
            if (Database::$throwOnRead) throw new \RuntimeException('database unavailable');
            foreach ($this->filtered() as $row) return (object) $row;
            return null;
        }
        /** @return list<object> */
        public function get(): array
        {
            if (Database::$throwOnRead) throw new \RuntimeException('database unavailable');
            $rows = $this->filtered();
            if ($this->orderBy !== null) {
                $column = $this->orderBy;
                usort($rows, static fn(array $a, array $b): int => ($a[$column] ?? null) <=> ($b[$column] ?? null));
            }
            return array_map(static fn(array $row): object => (object) $row, $rows);
        }
        public function delete(): int
        {
            $rows =& Database::$tables[$this->table]; $rows ??= [];
            $before = count($rows);
            $rows = array_values(array_filter($rows, fn(array $row): bool => ! $this->matches($row, $this->where)));
            return $before - count($rows);
        }
        /** @return list<array<string,mixed>> */
        private function filtered(): array
        {
            return array_values(array_filter(Database::$tables[$this->table] ?? [], fn(array $row): bool => $this->matches($row, $this->where)));
        }
        /** @param array<string,mixed> $row @param array<string,mixed> $conditions */
        private function matches(array $row, array $conditions): bool
        {
            foreach ($conditions as $column => $value) {
                if ($value === '__NOT_NULL__') { if (($row[$column] ?? null) === null) return false; continue; }
                if (($row[$column] ?? null) !== $value) return false;
            }
            return true;
        }
    }
}

namespace Illuminate\Support\Facades {
    final class DB
    {
        public static function table(string $table): \Tests\TitanAI\Pass3\Fakes\Query
        { return new \Tests\TitanAI\Pass3\Fakes\Query($table); }
    }
    final class Event
    {
        /** @var list<object> */ public static array $events = [];
        public static bool $throw = false;
        /** @var array<string,list<callable>> */ public static array $listeners = [];
        public static function dispatch(object $event): object
        {
            if (self::$throw) throw new \RuntimeException('listener exploded');
            self::$events[] = $event;
            foreach (self::$listeners[$event::class] ?? [] as $listener) $listener($event);
            return $event;
        }
        public static function listen(string $event, callable|string $listener): void
        { if (is_callable($listener)) self::$listeners[$event][] = $listener; }
        public static function reset(): void { self::$events = []; self::$listeners = []; self::$throw = false; }
    }
    final class Log
    {
        /** @var list<array<string,mixed>> */ public static array $records = [];
        public static function warning(string $message, array $context = []): void { self::$records[] = compact('message', 'context'); }
        public static function debug(string $message, array $context = []): void { self::$records[] = compact('message', 'context'); }
        public static function reset(): void { self::$records = []; }
    }
}

namespace App\Extensions\AIAgent\System\Models {
    class AIAgentWorkflow { public int $user_id = 42; }
    class AIAgentWorkflowRun {}
}
namespace App\Extensions\AIChatPro\System\Connectors\Models { class AIChatProConnector {} }

namespace {
    function collect(array $items = []): \Illuminate\Support\Collection { return new \Illuminate\Support\Collection($items); }
    function now(): string { static $tick = 0; return sprintf('2026-08-03 11:00:%02d', ++$tick); }
    function config(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'titanai.memory.default_ttl' => null,
            'titanai.memory.emit_events' => true,
            'titanai.events.enabled' => true,
            'titanai.events.strict' => false,
            'titanai.events.idempotency_cache_size' => 100,
            'titanai.orchestration.rethrow_failures' => false,
            'titanai.features.cross_extension_actions' => true,
            'titanai.features.cross_extension_connectors' => true,
            'titanai.extensions.aiagent.memory_bridge.enabled' => true,
            'titanai.extensions.aiagent.memory_bridge.strict' => false,
            default => $default,
        };
    }

    $root = dirname(__DIR__, 2);
    $require = static function (string $file) use ($root): void { require_once $root . '/' . $file; };

    $require('app/Domains/TitanAI/Contracts/Registrable.php');
    $require('app/Domains/TitanAI/Contracts/SkillDefinition.php');
    $require('app/Domains/TitanAI/Contracts/ActionDefinition.php');
    $require('app/Domains/TitanAI/Contracts/ConnectorDefinition.php');
    $require('app/Domains/TitanAI/Contracts/ToolDefinition.php');
    $require('app/Domains/TitanAI/Registries/UnifiedRegistry.php');
    $require('app/Domains/TitanAI/ActionInvoked.php');
    $require('app/Domains/TitanAI/ActionCompleted.php');
    $require('app/Domains/TitanAI/ActionFailed.php');
    $require('app/Domains/TitanAI/Diagnostics/TitanAIDiagnostics.php');
    $require('app/Domains/TitanAI/Events/TitanAIEventBus.php');
    $require('app/Domains/TitanAI/Orchestration/CrossExtensionOrchestrator.php');
    $require('app/Domains/TitanAI/Memory/Enums/MemoryScope.php');
    $require('app/Domains/TitanAI/UserMemoryUpdated.php');
    $require('app/Domains/TitanAI/WorkflowMemoryUpdated.php');
    $require('app/Domains/TitanAI/Memory/Services/UnifiedMemoryRepository.php');
    $require('app/Extensions/AIAgent/System/Memory/UnifiedMemoryBridge.php');
    $require('app/Extensions/AIAgent/System/Actions/Contracts/ActionInterface.php');
    $require('app/Extensions/AIAgent/System/Actions/Contracts/AIAgentActionInterface.php');
    $require('app/Extensions/AIAgent/System/Actions/UnifiedActionAdapter.php');
    $require('app/Extensions/AIAgent/System/Engine/AIAgentActionRegistry.php');
    $require('app/Extensions/AIChatPro/System/Connectors/ConnectorDefinition.php');
    $require('app/Extensions/AIChatPro/System/Connectors/ConnectorRegistry.php');
    $require('app/Extensions/Chatbot/System/TitanAI/Contracts/MemoryContextProviderContract.php');
    $require('app/Extensions/Chatbot/System/TitanAI/DTO/TitanAIRequest.php');
    $require('app/Extensions/Chatbot/System/TitanAI/Context/MemoryContextProvider.php');

    eval(<<<'PHP'
namespace Tests\TitanAI\Pass3\Fixtures;

use App\Domains\TitanAI\Contracts\ActionDefinition;
use App\Domains\TitanAI\Contracts\ConnectorDefinition;
use App\Extensions\AIAgent\System\Actions\Contracts\ActionInterface;
use App\Extensions\AIAgent\System\Actions\Contracts\AIAgentActionInterface;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflow;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun;
use App\Extensions\AIChatPro\System\Connectors\ConnectorDefinition as NativeConnectorDefinition;
use App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector;

final class NativeActionOne implements ActionInterface
{
    public function execute(array $config, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array { return $context; }
}
final class NativeActionTwo implements ActionInterface
{
    public function execute(array $config, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array { return $context; }
}
final class UnifiedNativeAction implements AIAgentActionInterface
{
    public int $executions = 0;
    public function getCategory(): string { return 'test'; }
    public function getLabel(): string { return 'Unified native'; }
    public function getDescription(): string { return 'Tests lifecycle isolation'; }
    public function getIcon(): string { return 'bolt'; }
    public function getConfigSchema(): array { return []; }
    public function execute(array $config, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array
    {
        $this->executions++;
        return [...$context, 'native_completed' => true];
    }
}
final class NativeConnectorOne implements NativeConnectorDefinition
{
    public function key(): string { return 'native-one'; }
    public function label(): string { return 'Native One'; }
    public function description(): string { return 'Native connector'; }
    public function icon(): string { return 'plug'; }
    public function redirectRoute(): string { return 'native.one'; }
    public function isEnabled(): bool { return true; }
    public function tools(AIChatProConnector $connector, string $engine): array { return []; }
    public function handleToolCall(string $functionName, array $arguments, AIChatProConnector $connector): array { return []; }
    public function systemPromptHint(AIChatProConnector $connector): ?string { return null; }
    public function meta(AIChatProConnector $connector): array { return ['name' => 'Native One', 'key' => 'native-one', 'icon' => 'plug']; }
    public function details(): array { return ['type' => 'demo', 'author' => 'test', 'uuid' => 'demo', 'website' => '']; }
    public function permissions(): array { return []; }
    public function accessOptions(AIChatProConnector $connector): array { return []; }
    public function revokeToken(AIChatProConnector $connector): void {}
}

final class GoodAction implements ActionDefinition
{
    public function key(): string { return 'good'; }
    public function name(): string { return 'Good'; }
    public function description(): string { return 'Works'; }
    public function metadata(): array { return []; }
    public function execute(array $payload, array $context = []): mixed { return ['ok' => $payload['value'] ?? null]; }
    public function inputSchema(): array { return []; }
    public function outputSchema(): array { return []; }
}
final class BadAction implements ActionDefinition
{
    public function key(): string { return 'bad'; }
    public function name(): string { return 'Bad'; }
    public function description(): string { return 'Fails'; }
    public function metadata(): array { return []; }
    public function execute(array $payload, array $context = []): mixed { throw new \RuntimeException('action failed'); }
    public function inputSchema(): array { return []; }
    public function outputSchema(): array { return []; }
}
final class GoodConnector implements ConnectorDefinition
{
    public function key(): string { return 'connector'; }
    public function name(): string { return 'Connector'; }
    public function description(): string { return 'Works'; }
    public function metadata(): array { return []; }
    public function isConfigured(): bool { return true; }
    public function configSchema(): array { return []; }
    public function send(array $data): mixed { return ['sent' => $data['value'] ?? null]; }
    public function receive(): array { return []; }
}
PHP);

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void { if (! $condition) $failures[] = $message; };

    $diagnostics = new \App\Domains\TitanAI\Diagnostics\TitanAIDiagnostics(limit: 20);
    $events = new \App\Domains\TitanAI\Events\TitanAIEventBus($diagnostics);

    \Illuminate\Support\Facades\Event::reset();
    \Illuminate\Support\Facades\Event::$throw = true;
    $result = $events->dispatch(new \stdClass(), 'event:one');
    $check($result === false, 'Event bus did not isolate a throwing listener.');
    $check(($diagnostics->snapshot()['events']['failed'] ?? 0) === 1, 'Event bus failure was not recorded in diagnostics.');

    \Illuminate\Support\Facades\Event::$throw = false;
    $check($events->dispatch(new \stdClass(), 'event:one') === false, 'A failed event idempotency key was redelivered after possible partial listener side effects.');
    $check($events->dispatch(new \stdClass(), 'event:two') === true, 'Event bus rejected a first delivery.');
    $check($events->dispatch(new \stdClass(), 'event:two') === false, 'Event bus delivered the same idempotency key twice.');
    $check(count(\Illuminate\Support\Facades\Event::$events) === 1, 'Duplicate event reached the underlying dispatcher.');

    $native = new \Tests\TitanAI\Pass3\Fixtures\UnifiedNativeAction();
    $adapter = new \App\Extensions\AIAgent\System\Actions\UnifiedActionAdapter('native', $native, $events);
    \Illuminate\Support\Facades\Event::$throw = true;
    $adapterResult = $adapter->execute(
        ['value' => 1],
        [
            'workflow' => new \App\Extensions\AIAgent\System\Models\AIAgentWorkflow(),
            'run' => new \App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun(),
            'state' => ['existing' => true],
            'correlation_id' => 'corr-pass3',
        ],
    );
    $check(($adapterResult['native_completed'] ?? false) === true && $native->executions === 1, 'A throwing lifecycle listener changed the native action outcome.');
    \Illuminate\Support\Facades\Event::$throw = false;

    $listenerCalls = 0;
    $events->listenOnce('pass3.listener', \stdClass::class, static function () use (&$listenerCalls): void { $listenerCalls++; });
    $events->listenOnce('pass3.listener', \stdClass::class, static function () use (&$listenerCalls): void { $listenerCalls += 100; });
    \Illuminate\Support\Facades\Event::dispatch(new \stdClass());
    $check($listenerCalls === 1, 'listenOnce registered the same listener key more than once.');

    $nativeActions = new \App\Extensions\AIAgent\System\Engine\AIAgentActionRegistry();
    $nativeActionCalls = 0;
    $nativeActions->onRegistered(static function () use (&$nativeActionCalls): void { $nativeActionCalls++; }, replay: true, listenerKey: 'unified');
    $nativeActions->onRegistered(static function () use (&$nativeActionCalls): void { $nativeActionCalls += 100; }, replay: true, listenerKey: 'unified');
    $nativeActions->register('one', \Tests\TitanAI\Pass3\Fixtures\NativeActionOne::class);
    $check($nativeActionCalls === 1, 'AI Agent named registration listeners were duplicated.');

    $nativeConnectors = new \App\Extensions\AIChatPro\System\Connectors\ConnectorRegistry();
    $nativeConnectorCalls = 0;
    $nativeConnectors->onRegistered(static function () use (&$nativeConnectorCalls): void { $nativeConnectorCalls++; }, replay: true, listenerKey: 'unified');
    $nativeConnectors->onRegistered(static function () use (&$nativeConnectorCalls): void { $nativeConnectorCalls += 100; }, replay: true, listenerKey: 'unified');
    $nativeConnectors->register('native-one', \Tests\TitanAI\Pass3\Fixtures\NativeConnectorOne::class);
    $check($nativeConnectorCalls === 1, 'AIChatPro named registration listeners were duplicated.');

    $registry = new \App\Domains\TitanAI\Registries\UnifiedRegistry();
    $registry->registerAction('good', new \Tests\TitanAI\Pass3\Fixtures\GoodAction());
    $registry->registerAction('bad', new \Tests\TitanAI\Pass3\Fixtures\BadAction());
    $registry->registerConnector('connector', new \Tests\TitanAI\Pass3\Fixtures\GoodConnector());
    $orchestrator = new \App\Domains\TitanAI\Orchestration\CrossExtensionOrchestrator($registry, $diagnostics);
    $good = $orchestrator->executeAction('good', ['value' => 7]);
    $check(($good['ok'] ?? false) === true && ($good['result']['ok'] ?? null) === 7, 'Cross-extension action orchestration did not return a successful structured result.');
    $bad = $orchestrator->executeAction('bad', []);
    $check(($bad['ok'] ?? true) === false && ($bad['error_code'] ?? null) === 'action_execution_failed', 'Cross-extension action failure was not isolated.');
    $check(($bad['message'] ?? null) === 'TitanAI action execution failed.', 'Cross-extension action failure exposed the native exception message.');
    $sent = $orchestrator->send('connector', ['value' => 9]);
    $check(($sent['ok'] ?? false) === true && ($sent['result']['sent'] ?? null) === 9, 'Cross-extension connector orchestration failed.');

    \Tests\TitanAI\Pass3\Fakes\Database::reset();
    $memory = new \App\Domains\TitanAI\Memory\Services\UnifiedMemoryRepository('unified_memories', $events, $diagnostics);
    $bridge = new \App\Extensions\AIAgent\System\Memory\UnifiedMemoryBridge($memory, $diagnostics);
    $bridge->remember(42, 5, 'Customer prefers SMS');
    $values = $memory->allForEntity('user', '42', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER);
    $check(($values['aiagent.memory.5']['memory'] ?? null) === 'Customer prefers SMS', 'AI Agent memory was not mirrored into unified user memory.');
    $bridge->forget(42, 5);
    $check(! $memory->has('user', '42', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'aiagent.memory.5'), 'AI Agent memory deletion was not mirrored.');

    $bridge->remember(42, 6, 'Uses eco products');
    $memory->store('workflow', 'wf-9', \App\Domains\TitanAI\Memory\Enums\MemoryScope::WORKFLOW, 'current_job', ['job' => 'cleaning'], source: 'chatbot');
    $request = new \App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest(
        message: 'What next?', tenantId: '1', userId: '42', deviceId: 'd1', channel: 'web', conversationSource: 'chatbot',
        workflowId: 'wf-9', payload: ['memory' => ['manual' => 'keep']],
    );
    $provider = new \App\Extensions\Chatbot\System\TitanAI\Context\MemoryContextProvider($memory, $diagnostics);
    $messages = $provider->context($request);
    $encoded = json_encode($messages, JSON_THROW_ON_ERROR);
    $check(str_contains($encoded, 'Uses eco products') && str_contains($encoded, 'current_job') && str_contains($encoded, 'manual'), 'Chatbot memory context did not merge payload, user, and workflow memory.');

    \Tests\TitanAI\Pass3\Fakes\Database::$throwOnRead = true;
    $messages = $provider->context($request);
    $check($messages !== [], 'Chatbot memory failure isolation removed the existing conversation context.');
    $check(($diagnostics->snapshot()['memory']['failed'] ?? 0) >= 1, 'Chatbot memory read failure was not recorded.');

    $snapshot = $diagnostics->snapshot($registry);
    $check(($snapshot['registry']['total'] ?? null) === 3, 'Diagnostics did not include the unified registry state.');
    $check(isset($snapshot['health']), 'Diagnostics did not expose a health status.');

    if ($failures !== []) {
        fwrite(STDERR, "TitanAI pass 3 orchestration FAILED (" . count($failures) . " issues):\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "TitanAI pass 3 orchestration PASSED\n";
}
