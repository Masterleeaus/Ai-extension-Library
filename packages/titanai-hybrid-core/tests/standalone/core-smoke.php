<?php

declare(strict_types=1);

namespace Illuminate\Support {
    final class Collection implements \Countable {
        public function __construct(private array $items = []) {}
        public function put(string $key, mixed $value): self { $this->items[$key] = $value; return $this; }
        public function get(string $key): mixed { return $this->items[$key] ?? null; }
        public function has(string $key): bool { return array_key_exists($key, $this->items); }
        public function keys(): self { return new self(array_keys($this->items)); }
        public function values(): self { return new self(array_values($this->items)); }
        public function all(): array { return $this->items; }
        public function count(): int { return count($this->items); }
        public function mapWithKeys(callable $callback): self { $out=[]; foreach ($this->items as $k=>$v) $out += $callback($v,$k); return new self($out); }
    }
}

namespace {
    function collect(array $items = []): \Illuminate\Support\Collection { return new \Illuminate\Support\Collection($items); }
    $root = dirname(__DIR__, 2);
    foreach (['Registrable','SkillDefinition','ActionDefinition','ConnectorDefinition','ToolDefinition'] as $file) require_once $root . '/src/Contracts/' . $file . '.php';
    require_once $root . '/src/Registries/UnifiedRegistry.php';
    $skill = new class implements \TitanAI\Hybrid\Contracts\SkillDefinition {
        public function key(): string { return 'smoke'; } public function name(): string { return 'Smoke'; }
        public function description(): string { return 'Smoke test'; } public function metadata(): array { return []; }
        public function canHandle(string $intent): bool { return true; } public function handle(string $intent, array $context = []): string { return 'ok'; } public function trainingExamples(): array { return []; }
    };
    $registry = new \TitanAI\Hybrid\Registries\UnifiedRegistry();
    $registry->registerSkill('smoke', $skill);
    if ($registry->counts()['total'] !== 1) throw new \RuntimeException('Registry smoke failed.');
    echo "TitanAI Composer core smoke PASSED\n";
}
