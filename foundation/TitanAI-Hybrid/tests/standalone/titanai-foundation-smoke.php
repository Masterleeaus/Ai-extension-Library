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
namespace Illuminate\Support\Facades { final class Event { public static array $events = []; public static function dispatch(object $event): object { self::$events[] = $event; return $event; } } }

namespace App\Extensions\AIAgent\System\Models {
    class AIAgentWorkflow {}
    class AIAgentWorkflowRun {}
}

namespace App\Extensions\AIChatPro\System\Connectors\Models {
    class AIChatProConnector {}
}

namespace {
    function collect(array $items = []): \Illuminate\Support\Collection
    {
        return new \Illuminate\Support\Collection($items);
    }

    $root = dirname(__DIR__, 2);
    $require = static function (string $file) use ($root): void { require_once $root . '/' . $file; };

    $require('app/Domains/TitanAI/Contracts/Registrable.php');
    $require('app/Domains/TitanAI/Contracts/SkillDefinition.php');
    $require('app/Domains/TitanAI/Contracts/ActionDefinition.php');
    $require('app/Domains/TitanAI/Contracts/ConnectorDefinition.php');
    $require('app/Domains/TitanAI/Contracts/ToolDefinition.php');
    $require('app/Domains/TitanAI/Registries/UnifiedRegistry.php');
    $require('app/Extensions/Chatbot/System/TitanAI/Runtime/Skills/UnifiedSkillAdapter.php');
    $require('app/Extensions/AIAgent/System/Actions/Contracts/ActionInterface.php');
    $require('app/Extensions/AIAgent/System/Actions/Contracts/AIAgentActionInterface.php');
    $require('app/Domains/TitanAI/ActionInvoked.php');
    $require('app/Domains/TitanAI/ActionCompleted.php');
    $require('app/Extensions/AIAgent/System/Actions/UnifiedActionAdapter.php');
    $require('app/Extensions/AIChatPro/System/Connectors/ConnectorDefinition.php');
    $require('app/Extensions/AIChatPro/System/Connectors/UnifiedConnectorAdapter.php');
    $require('app/Domains/TitanAI/ActionFailed.php');

    $assert = static function (bool $condition, string $message): void {
        if (! $condition) {
            throw new \RuntimeException($message);
        }
    };

    $registry = new \App\Domains\TitanAI\Registries\UnifiedRegistry();
    $skill = new \App\Extensions\Chatbot\System\TitanAI\Runtime\Skills\UnifiedSkillAdapter([
        'slug' => 'deep-clean-planner',
        'description' => 'Plans deep cleaning work.',
        'capabilities' => ['deep-clean'],
        'tools' => ['jobs.read'],
        'instructions' => 'Verified instructions',
        'sha256' => str_repeat('a', 64),
    ]);
    $registry->registerSkill($skill->key(), $skill);
    $assert($skill->canHandle('Build a deep clean checklist'), 'Skill matching failed.');
    $assert($registry->counts()['skills'] === 1, 'Skill registration count failed.');

    $nativeAction = new class implements \App\Extensions\AIAgent\System\Actions\Contracts\AIAgentActionInterface {
        public function getCategory(): string { return 'utilities'; }
        public function getLabel(): string { return 'Echo'; }
        public function getDescription(): string { return 'Echo payload'; }
        public function getIcon(): string { return 'tabler-message'; }
        public function getConfigSchema(): array { return ['type' => 'object']; }
        public function execute(array $config, array $context, \App\Extensions\AIAgent\System\Models\AIAgentWorkflow $workflow, \App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun $run): array
        {
            return $context + ['echo' => $config['value'] ?? null];
        }
    };
    $action = new \App\Extensions\AIAgent\System\Actions\UnifiedActionAdapter('echo', $nativeAction);
    $registry->registerAction($action->key(), $action);
    $result = $action->execute(
        ['value' => 'ok'],
        [
            'workflow' => new \App\Extensions\AIAgent\System\Models\AIAgentWorkflow(),
            'run' => new \App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun(),
            'state' => ['before' => true],
        ],
    );
    $assert($result === ['before' => true, 'echo' => 'ok'], 'Action adapter delegation failed.');
    $assert(count(\Illuminate\Support\Facades\Event::$events) === 2, 'Action lifecycle events were not dispatched.');

    $nativeConnector = new class implements \App\Extensions\AIChatPro\System\Connectors\ConnectorDefinition {
        public function key(): string { return 'test'; }
        public function label(): string { return 'Test Connector'; }
        public function description(): string { return 'Test connector'; }
        public function icon(): string { return 'tabler-plug'; }
        public function redirectRoute(): string { return 'test.redirect'; }
        public function isEnabled(): bool { return true; }
        public function tools(\App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector $connector, string $engine): array { return []; }
        public function handleToolCall(string $functionName, array $arguments, \App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector $connector): array { return ['function' => $functionName, 'arguments' => $arguments]; }
        public function systemPromptHint(\App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector $connector): ?string { return 'hint'; }
        public function meta(\App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector $connector): array { return ['name' => 'Test', 'key' => 'test', 'icon' => 'tabler-plug']; }
        public function details(): array { return ['type' => 'test', 'author' => 'test', 'uuid' => 'test', 'website' => '']; }
        public function permissions(): array { return ['Read test data']; }
        public function accessOptions(\App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector $connector): array { return []; }
        public function revokeToken(\App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector $connector): void {}
    };
    $connector = new \App\Extensions\AIChatPro\System\Connectors\UnifiedConnectorAdapter($nativeConnector);
    $registry->registerConnector($connector->key(), $connector);
    $connectorResult = $connector->send([
        'connector' => new \App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector(),
        'function_name' => 'connector_test_read',
        'arguments' => ['id' => 7],
    ]);
    $assert($connectorResult['arguments']['id'] === 7, 'Connector adapter delegation failed.');

    $event = new \App\Domains\TitanAI\ActionFailed('test', 'echo');
    $assert($event->exception === null, 'Nullable action failure exception failed.');
    $assert($registry->counts() === ['skills' => 1, 'actions' => 1, 'connectors' => 1, 'tools' => 0, 'total' => 3], 'Registry totals failed.');

    echo "TitanAI standalone smoke PASSED (registry + skill/action/connector adapters + events)\n";
}
