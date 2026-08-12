<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Canonical;

use InvalidArgumentException;

final class CanonicalEntityRegistry
{
    /** @var array<string, CanonicalEntityDefinition> */
    private array $definitions = [];

    public function register(CanonicalEntityDefinition $definition): void
    {
        if (isset($this->definitions[$definition->key])) {
            throw new InvalidArgumentException("Canonical entity {$definition->key} is already registered.");
        }

        $this->definitions[$definition->key] = $definition;
        ksort($this->definitions);
    }

    public function get(string $key): CanonicalEntityDefinition
    {
        return $this->definitions[$key]
            ?? throw new InvalidArgumentException("Unknown canonical entity: {$key}");
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /** @return array<string, CanonicalEntityDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }

    /** @return array<string, CanonicalEntityDefinition> */
    public function available(ModuleAvailabilityResolver $modules): array
    {
        return array_filter(
            $this->definitions,
            static fn (CanonicalEntityDefinition $definition): bool => $definition->isAvailable($modules),
        );
    }
}
