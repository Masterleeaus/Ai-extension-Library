<?php

namespace App\Domains\WorkCore\Calculating\Services;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use InvalidArgumentException;

class CalculatingEngineRegistry
{
    protected array $engines = [];

    protected array $engineClasses = [];

    /**
     * Register an engine class
     */
    public function registerEngineClass(string $engineId, string $engineClass): self
    {
        if (! is_subclass_of($engineClass, CalculatingEngineContract::class)) {
            throw new InvalidArgumentException(
                "Engine class {$engineClass} must implement CalculatingEngineContract"
            );
        }

        $this->engineClasses[$engineId] = $engineClass;

        return $this;
    }

    /**
     * Register an engine instance
     */
    public function registerEngine(CalculatingEngineContract $engine): self
    {
        $engineId = $engine->getEngineId();
        $this->engines[$engineId] = $engine;
        $this->engineClasses[$engineId] = get_class($engine);

        return $this;
    }

    /**
     * Get a registered engine
     */
    public function getEngine(string $engineId): ?CalculatingEngineContract
    {
        if (! isset($this->engines[$engineId])) {
            if (isset($this->engineClasses[$engineId])) {
                $this->engines[$engineId] = app($this->engineClasses[$engineId]);
            } else {
                return null;
            }
        }

        return $this->engines[$engineId];
    }

    /**
     * Check if an engine is registered
     */
    public function hasEngine(string $engineId): bool
    {
        return isset($this->engines[$engineId]) || isset($this->engineClasses[$engineId]);
    }

    /**
     * Get all registered engines
     */
    public function getAllEngines(): array
    {
        $engines = [];

        foreach (array_keys($this->engineClasses) as $engineId) {
            $engines[$engineId] = $this->getEngine($engineId);
        }

        return $engines;
    }

    /**
     * Get enabled engines
     */
    public function getEnabledEngines(): array
    {
        return array_filter(
            $this->getAllEngines(),
            fn ($engine) => $engine !== null && $engine->isEnabled()
        );
    }

    /**
     * Get engines by type
     */
    public function getEnginesByType(string $type): array
    {
        return array_filter(
            $this->getAllEngines(),
            fn ($engine) => $engine !== null && $engine->getEngineType() === $type
        );
    }

    /**
     * Get engines ordered by priority
     */
    public function getEnginesByPriority(array $engineIds = []): array
    {
        $engines = [];

        if (empty($engineIds)) {
            $engines = $this->getEnabledEngines();
        } else {
            foreach ($engineIds as $engineId) {
                $engine = $this->getEngine($engineId);
                if ($engine && $engine->isEnabled()) {
                    $engines[$engineId] = $engine;
                }
            }
        }

        // Sort by priority
        uasort($engines, fn ($a, $b) => $a->getPriority() <=> $b->getPriority());

        return $engines;
    }

    /**
     * Unregister an engine
     */
    public function unregisterEngine(string $engineId): self
    {
        unset($this->engines[$engineId]);
        unset($this->engineClasses[$engineId]);

        return $this;
    }

    /**
     * Get engine metadata
     */
    public function getEngineMetadata(string $engineId): ?array
    {
        $engine = $this->getEngine($engineId);

        if (! $engine) {
            return null;
        }

        return [
            'id' => $engine->getEngineId(),
            'name' => $engine->getEngineName(),
            'version' => $engine->getEngineVersion(),
            'type' => $engine->getEngineType(),
            'enabled' => $engine->isEnabled(),
            'priority' => $engine->getPriority(),
            'config' => $engine->getConfig(),
            'metadata' => $engine->getMetadata(),
        ];
    }

    /**
     * Get all engines metadata
     */
    public function getAllEnginesMetadata(): array
    {
        $metadata = [];

        foreach (array_keys($this->engineClasses) as $engineId) {
            $meta = $this->getEngineMetadata($engineId);
            if ($meta) {
                $metadata[$engineId] = $meta;
            }
        }

        return $metadata;
    }

    /**
     * Check for conflicts between engines
     */
    public function getConflicts(): array
    {
        $conflicts = [];
        $engines = $this->getEnabledEngines();
        $engineIds = array_keys($engines);

        for ($i = 0; $i < count($engineIds); $i++) {
            for ($j = $i + 1; $j < count($engineIds); $j++) {
                $engineA = $engines[$engineIds[$i]];
                $engineB = $engines[$engineIds[$j]];

                if ($engineA->hasConflictWith($engineIds[$j])) {
                    $conflicts[] = [
                        'engine_a' => $engineIds[$i],
                        'engine_b' => $engineIds[$j],
                        'resolution' => $engineA->getConflictResolution($engineIds[$j]),
                    ];
                }
            }
        }

        return $conflicts;
    }
}
