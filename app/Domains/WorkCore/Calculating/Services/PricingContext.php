<?php

namespace App\Domains\WorkCore\Calculating\Services;

use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use Carbon\Carbon;

class PricingContext implements PricingContextContract
{
    protected string $companyId;

    protected float $basePrice;

    protected float $currentPrice;

    protected array $resource;

    protected array $contextData = [];

    protected array $appliedEngines = [];

    protected array $engineResults = [];

    protected array $priceHistory = [];

    protected array $executionLog = [];

    public function __construct(
        string $companyId,
        float $basePrice,
        array $resource = [],
        array $contextData = []
    ) {
        $this->companyId = $companyId;
        $this->basePrice = $basePrice;
        $this->currentPrice = $basePrice;
        $this->resource = $resource;
        $this->contextData = $contextData;

        // Add initial price history entry
        $this->addPriceHistoryEntry('initial', $basePrice, ['reason' => 'base_price']);
    }

    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function setBasePrice(float $price): self
    {
        $this->basePrice = $price;

        return $this;
    }

    public function getCurrentPrice(): float
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(float $price): self
    {
        $this->currentPrice = $price;

        return $this;
    }

    public function getResource(): array
    {
        return $this->resource;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }

    public function setContextData(array $data): self
    {
        $this->contextData = $data;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->contextData[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->contextData[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->contextData[$key]);
    }

    public function getAppliedEngines(): array
    {
        return $this->appliedEngines;
    }

    public function addAppliedEngine(string $engineId, array $result): self
    {
        $this->appliedEngines[] = $engineId;
        $this->engineResults[$engineId] = $result;

        return $this;
    }

    public function getEngineResult(string $engineId): ?array
    {
        return $this->engineResults[$engineId] ?? null;
    }

    public function getAllEngineResults(): array
    {
        return $this->engineResults;
    }

    public function getPriceHistory(): array
    {
        return $this->priceHistory;
    }

    public function addPriceHistoryEntry(string $engineId, float $price, array $details): self
    {
        $this->priceHistory[] = [
            'engine_id' => $engineId,
            'price' => $price,
            'timestamp' => Carbon::now()->toIso8601String(),
            'details' => $details,
        ];

        return $this;
    }

    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    public function addLogEntry(string $message, string $level = 'info'): self
    {
        $this->executionLog[] = [
            'message' => $message,
            'level' => $level,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];

        return $this;
    }
}
