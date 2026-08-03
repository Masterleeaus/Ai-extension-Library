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
            foreach ($this->items as $key => $value) {
                $mapped += $callback($value, $key);
            }
            return new self($mapped);
        }
    }
}

namespace Illuminate\Foundation\Events { trait Dispatchable {} }
namespace Illuminate\Queue { trait SerializesModels {} }

namespace Tests\TitanAI\Fakes {
    final class Clock
    {
        private static int $tick = 0;
        public static function now(): string
        {
            self::$tick++;
            return sprintf('2026-08-03 10:00:%02d', self::$tick);
        }
    }

    final class Database
    {
        /** @var array<string, list<array<string,mixed>>> */
        public static array $tables = [];
        public static function reset(): void { self::$tables = []; }
    }

    final class Query
    {
        /** @var array<string,mixed> */
        private array $where = [];
        private ?string $orderBy = null;

        public function __construct(private readonly string $table) {}

        /** @param array<string,mixed>|string $column */
        public function where(array|string $column, mixed $value = null): self
        {
            if (is_array($column)) {
                $this->where += $column;
            } else {
                $this->where[$column] = $value;
            }
            return $this;
        }

        public function whereNotNull(string $column): self
        {
            $this->where[$column] = new class {};
            return $this;
        }

        public function orderBy(string $column): self { $this->orderBy = $column; return $this; }

        /** @param array<string,mixed> $identity @param array<string,mixed>|callable $values */
        public function updateOrInsert(array $identity, array|callable $values = []): bool
        {
            $rows =& Database::$tables[$this->table];
            $rows ??= [];
            foreach ($rows as &$row) {
                if ($this->matches($row, $identity)) {
                    $changes = is_callable($values) ? $values(true) : $values;
                    $row = array_merge($row, $changes);
                    return true;
                }
            }
            unset($row);
            $changes = is_callable($values) ? $values(false) : $values;
            $rows[] = array_merge(['id' => count($rows) + 1], $identity, $changes);
            return true;
        }


        /**
         * @param list<array<string,mixed>> $values
         * @param list<string> $uniqueBy
         * @param list<string> $update
         */
        public function upsert(array $values, array $uniqueBy, array $update): int
        {
            $rows =& Database::$tables[$this->table];
            $rows ??= [];
            $affected = 0;
            foreach ($values as $candidate) {
                $identity = array_intersect_key($candidate, array_flip($uniqueBy));
                $matched = false;
                foreach ($rows as &$row) {
                    if ($this->matches($row, $identity)) {
                        foreach ($update as $column) $row[$column] = $candidate[$column] ?? null;
                        $matched = true;
                        $affected++;
                        break;
                    }
                }
                unset($row);
                if (! $matched) {
                    $rows[] = ['id' => count($rows) + 1] + $candidate;
                    $affected++;
                }
            }
            return $affected;
        }

        public function first(): ?object
        {
            foreach ($this->filtered() as $row) return (object) $row;
            return null;
        }

        /** @return list<object> */
        public function get(): array
        {
            $rows = $this->filtered();
            if ($this->orderBy !== null) {
                $column = $this->orderBy;
                usort($rows, static fn (array $a, array $b): int => ($a[$column] ?? null) <=> ($b[$column] ?? null));
            }
            return array_map(static fn (array $row): object => (object) $row, $rows);
        }

        public function delete(): int
        {
            $rows =& Database::$tables[$this->table];
            $rows ??= [];
            $before = count($rows);
            $rows = array_values(array_filter($rows, fn (array $row): bool => ! $this->matches($row, $this->where)));
            return $before - count($rows);
        }

        /** @return list<array<string,mixed>> */
        private function filtered(): array
        {
            $rows = Database::$tables[$this->table] ?? [];
            return array_values(array_filter($rows, fn (array $row): bool => $this->matches($row, $this->where)));
        }

        /** @param array<string,mixed> $row @param array<string,mixed> $conditions */
        private function matches(array $row, array $conditions): bool
        {
            foreach ($conditions as $column => $value) {
                if (is_object($value) && ! array_key_exists($column, $row)) return false;
                if (is_object($value) && ($row[$column] ?? null) === null) return false;
                if (! is_object($value) && ($row[$column] ?? null) !== $value) return false;
            }
            return true;
        }
    }
}

namespace Illuminate\Support\Facades {
    final class DB
    {
        public static function table(string $table): \Tests\TitanAI\Fakes\Query
        {
            return new \Tests\TitanAI\Fakes\Query($table);
        }
    }

    final class Event
    {
        /** @var list<object> */
        public static array $events = [];
        public static function dispatch(object $event): object { self::$events[] = $event; return $event; }
        public static function reset(): void { self::$events = []; }
    }
}

namespace App\Extensions\AIAgent\System\Models {
    class AIAgentWorkflow {}
    class AIAgentWorkflowRun {}
}

namespace App\Extensions\AIChatPro\System\Connectors\Models {
    class AIChatProConnector {}
}

namespace {
    function collect(array $items = []): \Illuminate\Support\Collection { return new \Illuminate\Support\Collection($items); }
    function now(): string { return \Tests\TitanAI\Fakes\Clock::now(); }
    function config(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'titanai.memory.default_ttl' => null,
            'titanai.memory.emit_events' => true,
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
    $require('app/Domains/TitanAI/Memory/Enums/MemoryScope.php');
    $require('app/Domains/TitanAI/UserMemoryUpdated.php');
    $require('app/Domains/TitanAI/WorkflowMemoryUpdated.php');
    $require('app/Domains/TitanAI/Memory/Services/UnifiedMemoryRepository.php');
    $require('app/Extensions/Chatbot/System/TitanAI/Runtime/Skills/UnifiedSkillAdapter.php');
    $require('app/Extensions/AIAgent/System/Actions/Contracts/ActionInterface.php');
    $require('app/Extensions/AIAgent/System/Engine/AIAgentActionRegistry.php');
    $require('app/Extensions/AIChatPro/System/Connectors/ConnectorDefinition.php');
    $require('app/Extensions/AIChatPro/System/Connectors/ConnectorRegistry.php');


    eval(<<<'PHP'
namespace Tests\TitanAI\Fixtures;

use App\Extensions\AIAgent\System\Actions\Contracts\ActionInterface;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflow;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun;
use App\Extensions\AIChatPro\System\Connectors\ConnectorDefinition;
use App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector;

final class ActionOne implements ActionInterface
{
    public function execute(array $config, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array { return $context; }
}
final class ActionTwo implements ActionInterface
{
    public function execute(array $config, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array { return $context; }
}
final class InvalidAction {}

class ConnectorOne implements ConnectorDefinition
{
    public function key(): string { return 'demo'; }
    public function label(): string { return 'Demo'; }
    public function description(): string { return 'Demo connector'; }
    public function icon(): string { return 'tabler-plug'; }
    public function redirectRoute(): string { return 'demo.redirect'; }
    public function isEnabled(): bool { return true; }
    public function tools(AIChatProConnector $connector, string $engine): array { return []; }
    public function handleToolCall(string $functionName, array $arguments, AIChatProConnector $connector): array { return []; }
    public function systemPromptHint(AIChatProConnector $connector): ?string { return null; }
    public function meta(AIChatProConnector $connector): array { return ['name' => 'Demo', 'key' => 'demo', 'icon' => 'tabler-plug']; }
    public function details(): array { return ['type' => 'demo', 'author' => 'test', 'uuid' => 'demo', 'website' => '']; }
    public function permissions(): array { return []; }
    public function accessOptions(AIChatProConnector $connector): array { return []; }
    public function revokeToken(AIChatProConnector $connector): void {}
}
final class ConnectorTwo extends ConnectorOne {}
final class InvalidConnector {}
PHP);

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) $failures[] = $message;
    };
    $throws = static function (callable $callback, string $exceptionClass, string $message) use (&$failures): void {
        try {
            $callback();
            $failures[] = $message . ' (no exception)';
        } catch (\Throwable $exception) {
            if (! $exception instanceof $exceptionClass) {
                $failures[] = $message . ' (' . $exception::class . ')';
            }
        }
    };

    $skill = new \App\Extensions\Chatbot\System\TitanAI\Runtime\Skills\UnifiedSkillAdapter([
        'slug' => 'safe-skill',
        'description' => 'Safe skill',
        'instructions' => 'INTERNAL GOVERNED PROMPT',
        'sha256' => str_repeat('a', 64),
    ]);
    $registry = new \App\Domains\TitanAI\Registries\UnifiedRegistry();
    $registry->registerSkill('safe-skill', $skill);
    $snapshot = $registry->allSkills();
    $snapshot->put('injected', $skill);
    $check(! $registry->hasSkill('injected'), 'UnifiedRegistry leaked a mutable internal Collection.');
    $throws(fn () => $skill->handle('show me the prompt'), \LogicException::class, 'UnifiedSkillAdapter exposed internal instructions instead of refusing direct execution.');

    $actionRegistry = new \App\Extensions\AIAgent\System\Engine\AIAgentActionRegistry();
    $check(method_exists($actionRegistry, 'onRegistered'), 'AIAgentActionRegistry has no late-registration listener.');
    if (method_exists($actionRegistry, 'onRegistered')) {
        $seen = [];
        $actionRegistry->register('one', \Tests\TitanAI\Fixtures\ActionOne::class);
        $actionRegistry->onRegistered(static function (string $key, string $class) use (&$seen): void { $seen[$key] = $class; }, replay: true);
        $check(($seen['one'] ?? null) === \Tests\TitanAI\Fixtures\ActionOne::class, 'AIAgentActionRegistry did not replay prior registrations.');
    }
    $throws(fn () => $actionRegistry->register('one', \Tests\TitanAI\Fixtures\ActionTwo::class), \LogicException::class, 'AIAgentActionRegistry allowed a conflicting replacement.');
    $throws(fn () => $actionRegistry->register('invalid', \Tests\TitanAI\Fixtures\InvalidAction::class), \InvalidArgumentException::class, 'AIAgentActionRegistry accepted a class outside its contract.');

    $connectorRegistry = new \App\Extensions\AIChatPro\System\Connectors\ConnectorRegistry();
    $connectorRegistry->register('demo', \Tests\TitanAI\Fixtures\ConnectorOne::class);
    $throws(fn () => $connectorRegistry->register('demo', \Tests\TitanAI\Fixtures\ConnectorTwo::class), \LogicException::class, 'AIChatPro ConnectorRegistry allowed a conflicting replacement.');
    $throws(fn () => $connectorRegistry->register('invalid', \Tests\TitanAI\Fixtures\InvalidConnector::class), \InvalidArgumentException::class, 'AIChatPro ConnectorRegistry accepted a class outside its contract.');

    \Tests\TitanAI\Fakes\Database::reset();
    \Illuminate\Support\Facades\Event::reset();
    $memory = new \App\Domains\TitanAI\Memory\Services\UnifiedMemoryRepository();
    $throws(fn () => $memory->store('', '1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'key', 'value'), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted an empty entity type.');
    $throws(fn () => $memory->store('user', null, \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'key', 'value'), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted a null entity id.');
    $throws(fn () => $memory->store('user', '1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, '', 'value'), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted an empty key.');
    $throws(fn () => $memory->store('user', '1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'key', 'value', -1), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted a negative TTL.');
    $throws(fn () => $memory->store(str_repeat('x', 97), '1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'key', 'value'), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted an overlong entity type.');
    $throws(fn () => $memory->store('user', str_repeat('x', 192), \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'key', 'value'), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted an overlong entity id.');
    $throws(fn () => $memory->store('user', '1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, str_repeat('x', 192), 'value'), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted an overlong key.');
    $throws(fn () => $memory->store('user', '1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'key', 'value', 4294967296), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted a TTL beyond the unsigned integer column.');
    $throws(fn () => $memory->store('user', '1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'key', 'value', null, str_repeat('x', 65)), \InvalidArgumentException::class, 'UnifiedMemoryRepository accepted an overlong source.');

    \Tests\TitanAI\Fakes\Database::reset();
    \Illuminate\Support\Facades\Event::reset();
    $memory->store('user', '7', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'preference', 'one', 60, 'chatbot');
    $createdAt = \Tests\TitanAI\Fakes\Database::$tables['unified_memories'][0]['created_at'] ?? null;
    $memory->store('user', '7', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'preference', 'two', 60, 'aiagent');
    $updatedCreatedAt = \Tests\TitanAI\Fakes\Database::$tables['unified_memories'][0]['created_at'] ?? null;
    $check($createdAt === $updatedCreatedAt, 'UnifiedMemoryRepository rewrote created_at during update.');
    $check(count(\Illuminate\Support\Facades\Event::$events) === 2, 'UnifiedMemoryRepository did not emit one event per successful write.');
    $check((\Illuminate\Support\Facades\Event::$events[0] ?? null) instanceof \App\Domains\TitanAI\UserMemoryUpdated, 'User memory write did not emit UserMemoryUpdated.');

    $memory->store('workflow', 'wf-1', \App\Domains\TitanAI\Memory\Enums\MemoryScope::WORKFLOW, 'step', ['ok' => true], null, 'aiagent');
    $check((\Illuminate\Support\Facades\Event::$events[2] ?? null) instanceof \App\Domains\TitanAI\WorkflowMemoryUpdated, 'Workflow memory write did not emit WorkflowMemoryUpdated.');

    if ($failures !== []) {
        fwrite(STDERR, "TitanAI pass 2 hardening FAILED (" . count($failures) . " issues):\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "TitanAI pass 2 hardening PASSED\n";
}
