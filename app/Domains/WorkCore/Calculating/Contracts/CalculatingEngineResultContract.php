<?php

namespace App\Domains\WorkCore\Calculating\Contracts;

interface CalculatingEngineResultContract
{
    /**
     * Get the engine ID that produced this result
     */
    public function getEngineId(): string;

    /**
     * Check if calculation was successful
     */
    public function isSuccessful(): bool;

    /**
     * Get the calculated price
     */
    public function getCalculatedPrice(): float;

    /**
     * Get the price adjustment amount
     */
    public function getAdjustmentAmount(): float;

    /**
     * Get the price adjustment percentage
     */
    public function getAdjustmentPercentage(): float;

    /**
     * Get the reason for adjustment
     */
    public function getReason(): string;

    /**
     * Get all factors that influenced the calculation
     */
    public function getFactors(): array;

    /**
     * Get additional details
     */
    public function getDetails(): array;

    /**
     * Get error message if calculation failed
     */
    public function getErrorMessage(): ?string;

    /**
     * Get applied conditions
     */
    public function getAppliedConditions(): array;

    /**
     * Check if this result conflicts with another
     */
    public function conflictsWith(self $other): bool;

    /**
     * Get conflict severity (low, medium, high)
     */
    public function getConflictSeverity(self $other): string;

    /**
     * Get execution time in milliseconds
     */
    public function getExecutionTime(): float;

    /**
     * Get result metadata
     */
    public function getMetadata(): array;
}
