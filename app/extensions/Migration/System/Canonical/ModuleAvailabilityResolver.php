<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Canonical;

use InvalidArgumentException;

final class ModuleAvailabilityResolver
{
    /** @var array<string, bool> */
    private array $modules = [];

    /** @param array<string, bool> $modules */
    public function __construct(array $modules = [])
    {
        foreach ($modules as $module => $available) {
            $this->set((string) $module, (bool) $available);
        }
    }

    public function set(string $module, bool $available): void
    {
        $module = trim($module);
        if ($module === '') {
            throw new InvalidArgumentException('Module key cannot be empty.');
        }

        $this->modules[$module] = $available;
    }

    public function isAvailable(string $module): bool
    {
        return $this->modules[$module] ?? false;
    }

    /** @return array<string, bool> */
    public function all(): array
    {
        return $this->modules;
    }
}
