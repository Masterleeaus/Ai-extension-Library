<?php

declare(strict_types=1);
namespace App\Extensions\SystemAIChat\System\Capabilities;
final class CapabilityRegistry
{
    /** @var array<string, CapabilityDefinition> */
    private array $items = [];
    public function register(CapabilityDefinition $definition): void { $this->items[$definition->key] = $definition; }
    public function has(string $key): bool { return isset($this->items[$key]); }
    public function get(string $key): ?CapabilityDefinition { return $this->items[$key] ?? null; }
    /** @return array<string, CapabilityDefinition> */
    public function all(): array { return $this->items; }
}
