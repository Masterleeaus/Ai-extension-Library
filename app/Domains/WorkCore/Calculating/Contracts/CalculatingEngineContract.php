<?php

namespace App\Domains\WorkCore\Calculating\Contracts;

interface CalculatingEngineContract
{
    /**
     * Get the unique engine identifier
     */
    public function getEngineId(): string;

    /**
     * Get the engine name
     */
    public function getEngineName(): string;

    /**
     * Get the engine version
     */
    public function getEngineVersion(): string;

    /**
     * Get the engine type (pricing, discount, tax, loyalty, etc.)
     */
    public function getEngineType(): string;

    /**
     * Check if this engine is enabled
     */
    public function isEnabled(): bool;

    /**
     * Evaluate if this engine should apply to the given context
     */
    public function shouldApply(PricingContextContract $context): bool;

    /**
     * Calculate and return the result
     */
    public function calculate(PricingContextContract $context): CalculatingEngineResultContract;

    /**
     * Get engine configuration
     */
    public function getConfig(): array;

    /**
     * Get engine metadata
     */
    public function getMetadata(): array;

    /**
     * Get execution priority (lower = runs first)
     */
    public function getPriority(): int;

    /**
     * Check if this engine conflicts with another
     */
    public function hasConflictWith(string $engineId): bool;

    /**
     * Get conflict resolution strategy
     */
    public function getConflictResolution(string $engineId): string;
}
