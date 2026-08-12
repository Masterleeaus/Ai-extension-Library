<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Canonical;

use Closure;
use InvalidArgumentException;

final class ModuleAvailabilityResolver
{
    /** @var array<string, bool> */
    private array $modules = [];

    /** @param array<string, bool> $modules */
    public function __construct(
        array $modules = [],
        private readonly ?Closure $detector = null,
    ) {
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
        if (array_key_exists($module, $this->modules)) {
            return $this->modules[$module];
        }

        return $this->detector === null ? false : (bool) ($this->detector)($module);
    }

    /** @return array<string, bool> */
    public function all(): array
    {
        return $this->modules;
    }
}
