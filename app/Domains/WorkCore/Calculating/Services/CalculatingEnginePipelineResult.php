<?php

namespace App\Domains\WorkCore\Calculating\Services;

use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;

class CalculatingEnginePipelineResult
{
    protected bool $successful;

    protected float $finalPrice;

    protected array $engineResults;

    protected PricingContextContract $context;

    protected float $executionTime; // in milliseconds

    protected ?string $errorMessage;

    public function __construct(
        bool $successful,
        float $finalPrice,
        array $engineResults,
        PricingContextContract $context,
        float $executionTime,
        ?string $errorMessage = null
    ) {
        $this->successful = $successful;
        $this->finalPrice = $finalPrice;
        $this->engineResults = $engineResults;
        $this->context = $context;
        $this->executionTime = $executionTime;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Check if pipeline execution was successful
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Get final calculated price
     */
    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }

    /**
     * Get total price adjustment
     */
    public function getTotalAdjustment(): float
    {
        return $this->finalPrice - $this->context->getBasePrice();
    }

    /**
     * Get total adjustment percentage
     */
    public function getTotalAdjustmentPercentage(): float
    {
        $basePrice = $this->context->getBasePrice();
        if ($basePrice === 0) {
            return 0;
        }

        return (($this->finalPrice - $basePrice) / $basePrice) * 100;
    }

    /**
     * Get all engine results
     */
    public function getEngineResults(): array
    {
        return $this->engineResults;
    }

    /**
     * Get result for specific engine
     */
    public function getEngineResult(string $engineId): ?array
    {
        return $this->engineResults[$engineId]?->toArray() ?? null;
    }

    /**
     * Get applied engines
     */
    public function getAppliedEngines(): array
    {
        return array_keys(array_filter(
            $this->engineResults,
            fn ($result) => $result->isSuccessful()
        ));
    }

    /**
     * Get failed engines
     */
    public function getFailedEngines(): array
    {
        return array_keys(array_filter(
            $this->engineResults,
            fn ($result) => ! $result->isSuccessful()
        ));
    }

    /**
     * Get pricing context
     */
    public function getContext(): PricingContextContract
    {
        return $this->context;
    }

    /**
     * Get execution time
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Get error message if any
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Get execution log
     */
    public function getExecutionLog(): array
    {
        return $this->context->getExecutionLog();
    }

    /**
     * Get price history
     */
    public function getPriceHistory(): array
    {
        return $this->context->getPriceHistory();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'final_price' => $this->finalPrice,
            'base_price' => $this->context->getBasePrice(),
            'total_adjustment' => $this->getTotalAdjustment(),
            'total_adjustment_percentage' => $this->getTotalAdjustmentPercentage(),
            'applied_engines' => $this->getAppliedEngines(),
            'failed_engines' => $this->getFailedEngines(),
            'engine_results' => array_map(
                fn ($result) => $result->toArray(),
                $this->engineResults
            ),
            'execution_time_ms' => $this->executionTime,
            'error_message' => $this->errorMessage,
            'price_history' => $this->getPriceHistory(),
            'execution_log' => $this->getExecutionLog(),
        ];
    }
}
