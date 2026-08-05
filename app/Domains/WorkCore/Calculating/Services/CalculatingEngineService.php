<?php

namespace App\Domains\WorkCore\Calculating\Services;

use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;

class CalculatingEngineService
{
    protected CalculatingEngineRegistry $registry;

    protected CalculatingEnginePipeline $pipeline;

    public function __construct(
        CalculatingEngineRegistry $registry,
        CalculatingEnginePipeline $pipeline
    ) {
        $this->registry = $registry;
        $this->pipeline = $pipeline;
    }

    /**
     * Calculate price with specified engines
     */
    public function calculate(
        PricingContextContract $context,
        array $engineIds = [],
        array $options = []
    ): CalculatingEnginePipelineResult {
        // Configure pipeline
        if (empty($engineIds)) {
            $this->pipeline->useAllEngines();
        } else {
            $this->pipeline->setEngines($engineIds);
        }

        $this->pipeline
            ->stopOnError($options['stop_on_error'] ?? false)
            ->allowConflicts($options['allow_conflicts'] ?? false)
            ->setConflictResolution($options['conflict_resolution'] ?? 'maximum');

        // Execute pipeline
        return $this->pipeline->execute($context);
    }

    /**
     * Calculate price with default engines
     */
    public function calculateWithDefaults(PricingContextContract $context): CalculatingEnginePipelineResult
    {
        $this->pipeline->useAllEngines();

        return $this->pipeline->execute($context);
    }

    /**
     * Calculate price with single engine
     */
    public function calculateWithEngine(
        PricingContextContract $context,
        string $engineId,
        array $options = []
    ): CalculatingEnginePipelineResult {
        return $this->calculate($context, [$engineId], $options);
    }

    /**
     * Calculate price with engines of specific type
     */
    public function calculateByType(
        PricingContextContract $context,
        string $type,
        array $options = []
    ): CalculatingEnginePipelineResult {
        $this->pipeline->addEnginesByType($type);

        return $this->pipeline->execute($context);
    }

    /**
     * Get registry
     */
    public function getRegistry(): CalculatingEngineRegistry
    {
        return $this->registry;
    }

    /**
     * Get pipeline
     */
    public function getPipeline(): CalculatingEnginePipeline
    {
        return $this->pipeline;
    }

    /**
     * Register engine class
     */
    public function registerEngine(string $engineId, string $engineClass): self
    {
        $this->registry->registerEngineClass($engineId, $engineClass);

        return $this;
    }

    /**
     * Check if engine exists
     */
    public function hasEngine(string $engineId): bool
    {
        return $this->registry->hasEngine($engineId);
    }

    /**
     * Get engine
     */
    public function getEngine(string $engineId)
    {
        return $this->registry->getEngine($engineId);
    }

    /**
     * Get all engines metadata
     */
    public function getEnginesMetadata(): array
    {
        return $this->registry->getAllEnginesMetadata();
    }

    /**
     * Get engine metadata
     */
    public function getEngineMetadata(string $engineId): ?array
    {
        return $this->registry->getEngineMetadata($engineId);
    }

    /**
     * Get conflicts
     */
    public function getConflicts(): array
    {
        return $this->registry->getConflicts();
    }
}
