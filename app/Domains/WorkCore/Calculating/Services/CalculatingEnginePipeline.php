<?php

namespace App\Domains\WorkCore\Calculating\Services;

use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineResultContract;
use Illuminate\Support\Collection;

class CalculatingEnginePipeline
{
    protected CalculatingEngineRegistry $registry;

    protected array $engines = [];

    protected array $results = [];

    protected bool $stopOnError = false;

    protected bool $allowConflicts = false;

    protected string $conflictResolution = 'maximum'; // maximum, minimum, average, first, last

    public function __construct(CalculatingEngineRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Set engines to execute
     */
    public function setEngines(array $engineIds): self
    {
        $this->engines = $this->registry->getEnginesByPriority($engineIds);

        return $this;
    }

    /**
     * Add a single engine
     */
    public function addEngine(string $engineId): self
    {
        $engine = $this->registry->getEngine($engineId);
        if ($engine && $engine->isEnabled()) {
            $this->engines[$engineId] = $engine;
        }

        return $this;
    }

    /**
     * Add engines by type
     */
    public function addEnginesByType(string $type): self
    {
        $engines = $this->registry->getEnginesByType($type);
        $this->engines = array_merge($this->engines, $engines);

        return $this;
    }

    /**
     * Use all enabled engines
     */
    public function useAllEngines(): self
    {
        $this->engines = $this->registry->getEnabledEngines();

        return $this;
    }

    /**
     * Set whether to stop on error
     */
    public function stopOnError(bool $stop): self
    {
        $this->stopOnError = $stop;

        return $this;
    }

    /**
     * Set whether to allow conflicts
     */
    public function allowConflicts(bool $allow): self
    {
        $this->allowConflicts = $allow;

        return $this;
    }

    /**
     * Set conflict resolution strategy
     */
    public function setConflictResolution(string $strategy): self
    {
        $this->conflictResolution = $strategy;

        return $this;
    }

    /**
     * Execute the pipeline
     */
    public function execute(PricingContextContract $context): CalculatingEnginePipelineResult
    {
        $this->results = [];
        $executionStart = microtime(true);

        try {
            foreach ($this->engines as $engineId => $engine) {
                if (! $engine->shouldApply($context)) {
                    $context->addLogEntry(
                        "Engine {$engineId} skipped - conditions not met",
                        'info'
                    );
                    continue;
                }

                $engineStart = microtime(true);

                try {
                    $result = $engine->calculate($context);
                    $executionTime = (microtime(true) - $engineStart) * 1000;

                    $result->setExecutionTime($executionTime);
                    $this->results[$engineId] = $result;

                    if ($result->isSuccessful()) {
                        $context->addAppliedEngine($engineId, $result->toArray());
                        $context->addPriceHistoryEntry(
                            $engineId,
                            $result->getCalculatedPrice(),
                            [
                                'adjustment' => $result->getAdjustmentPercentage(),
                                'reason' => $result->getReason(),
                            ]
                        );
                        $context->setCurrentPrice($result->getCalculatedPrice());

                        $context->addLogEntry(
                            "Engine {$engineId} applied - adjustment: " .
                            round($result->getAdjustmentPercentage(), 2) . '%',
                            'info'
                        );
                    } else {
                        $context->addLogEntry(
                            "Engine {$engineId} failed: {$result->getErrorMessage()}",
                            'error'
                        );

                        if ($this->stopOnError) {
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $context->addLogEntry(
                        "Engine {$engineId} exception: {$e->getMessage()}",
                        'error'
                    );

                    if ($this->stopOnError) {
                        throw $e;
                    }
                }
            }

            // Check for conflicts
            $conflicts = $this->detectConflicts();
            if (! empty($conflicts) && ! $this->allowConflicts) {
                $context->addLogEntry(
                    'Conflicts detected: ' . json_encode($conflicts),
                    'warning'
                );
            }

            // Resolve conflicts if needed
            if (! empty($conflicts)) {
                $this->resolveConflicts($context);
            }
        } catch (\Exception $e) {
            $context->addLogEntry("Pipeline execution failed: {$e->getMessage()}", 'error');

            return new CalculatingEnginePipelineResult(
                false,
                $context->getCurrentPrice(),
                $this->results,
                $context,
                (microtime(true) - $executionStart) * 1000,
                $e->getMessage()
            );
        }

        return new CalculatingEnginePipelineResult(
            true,
            $context->getCurrentPrice(),
            $this->results,
            $context,
            (microtime(true) - $executionStart) * 1000
        );
    }

    /**
     * Detect conflicts between results
     */
    protected function detectConflicts(): array
    {
        $conflicts = [];
        $resultIds = array_keys($this->results);

        for ($i = 0; $i < count($resultIds); $i++) {
            for ($j = $i + 1; $j < count($resultIds); $j++) {
                $resultA = $this->results[$resultIds[$i]];
                $resultB = $this->results[$resultIds[$j]];

                if ($resultA->conflictsWith($resultB)) {
                    $conflicts[] = [
                        'engine_a' => $resultIds[$i],
                        'engine_b' => $resultIds[$j],
                        'severity' => $resultA->getConflictSeverity($resultB),
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Resolve conflicts
     */
    protected function resolveConflicts(PricingContextContract $context): void
    {
        $prices = array_map(
            fn ($result) => $result->getCalculatedPrice(),
            $this->results
        );

        $resolvedPrice = match ($this->conflictResolution) {
            'maximum' => max($prices),
            'minimum' => min($prices),
            'average' => array_sum($prices) / count($prices),
            'first' => reset($prices),
            'last' => end($prices),
            default => max($prices),
        };

        $context->setCurrentPrice($resolvedPrice);
    }

    /**
     * Get results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get result for engine
     */
    public function getResult(string $engineId): ?CalculatingEngineResultContract
    {
        return $this->results[$engineId] ?? null;
    }
}
