<?php

namespace App\Domains\WorkCore\Calculating\Services;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineResultContract;
use Carbon\Carbon;

class CalculatingEngineResult implements CalculatingEngineResultContract
{
    protected string $engineId;

    protected bool $successful = true;

    protected float $calculatedPrice;

    protected float $basePrice;

    protected string $reason = '';

    protected array $factors = [];

    protected array $details = [];

    protected ?string $errorMessage = null;

    protected array $appliedConditions = [];

    protected float $executionTime = 0;

    protected array $metadata = [];

    public function __construct(
        string $engineId,
        float $basePrice,
        float $calculatedPrice,
        string $reason = '',
        array $factors = [],
        array $details = []
    ) {
        $this->engineId = $engineId;
        $this->basePrice = $basePrice;
        $this->calculatedPrice = $calculatedPrice;
        $this->reason = $reason;
        $this->factors = $factors;
        $this->details = $details;
    }

    public static function success(
        string $engineId,
        float $basePrice,
        float $calculatedPrice,
        string $reason = '',
        array $factors = [],
        array $details = []
    ): self {
        return new self($engineId, $basePrice, $calculatedPrice, $reason, $factors, $details);
    }

    public static function failure(
        string $engineId,
        float $basePrice,
        string $errorMessage,
        array $details = []
    ): self {
        $result = new self($engineId, $basePrice, $basePrice, 'error', [], $details);
        $result->successful = false;
        $result->errorMessage = $errorMessage;

        return $result;
    }

    public function getEngineId(): string
    {
        return $this->engineId;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getCalculatedPrice(): float
    {
        return $this->calculatedPrice;
    }

    public function getAdjustmentAmount(): float
    {
        return $this->calculatedPrice - $this->basePrice;
    }

    public function getAdjustmentPercentage(): float
    {
        if ($this->basePrice === 0) {
            return 0;
        }

        return (($this->calculatedPrice - $this->basePrice) / $this->basePrice) * 100;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getFactors(): array
    {
        return $this->factors;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getAppliedConditions(): array
    {
        return $this->appliedConditions;
    }

    public function setAppliedConditions(array $conditions): self
    {
        $this->appliedConditions = $conditions;

        return $this;
    }

    public function conflictsWith(CalculatingEngineResultContract $other): bool
    {
        // Conflict if both apply opposite adjustments
        $thisAdjustment = $this->getAdjustmentPercentage();
        $otherAdjustment = $other->getAdjustmentPercentage();

        // Conflict if one increases and the other decreases by more than 5%
        return (($thisAdjustment > 5 && $otherAdjustment < -5) ||
                ($thisAdjustment < -5 && $otherAdjustment > 5));
    }

    public function getConflictSeverity(CalculatingEngineResultContract $other): string
    {
        if (! $this->conflictsWith($other)) {
            return 'none';
        }

        $totalAdjustment = abs($this->getAdjustmentPercentage() + $other->getAdjustmentPercentage());

        return match (true) {
            $totalAdjustment < 10 => 'low',
            $totalAdjustment < 25 => 'medium',
            default => 'high',
        };
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function setExecutionTime(float $milliseconds): self
    {
        $this->executionTime = $milliseconds;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'engine_id' => $this->engineId,
            'successful' => $this->successful,
            'calculated_price' => $this->calculatedPrice,
            'base_price' => $this->basePrice,
            'adjustment_amount' => $this->getAdjustmentAmount(),
            'adjustment_percentage' => $this->getAdjustmentPercentage(),
            'reason' => $this->reason,
            'factors' => $this->factors,
            'details' => $this->details,
            'error_message' => $this->errorMessage,
            'applied_conditions' => $this->appliedConditions,
            'execution_time' => $this->executionTime,
            'metadata' => $this->metadata,
        ];
    }
}
