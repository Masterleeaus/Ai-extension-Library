<?php

namespace App\Domains\WorkCore\Calculating\Contracts;

interface PricingContextContract
{
    /**
     * Get company ID
     */
    public function getCompanyId(): string;

    /**
     * Get base price
     */
    public function getBasePrice(): float;

    /**
     * Set base price
     */
    public function setBasePrice(float $price): self;

    /**
     * Get current price (after all engines applied)
     */
    public function getCurrentPrice(): float;

    /**
     * Set current price
     */
    public function setCurrentPrice(float $price): self;

    /**
     * Get resource information
     */
    public function getResource(): array;

    /**
     * Get context data
     */
    public function getContextData(): array;

    /**
     * Set context data
     */
    public function setContextData(array $data): self;

    /**
     * Get specific context value
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set specific context value
     */
    public function set(string $key, mixed $value): self;

    /**
     * Check if a key exists
     */
    public function has(string $key): bool;

    /**
     * Get applied engines
     */
    public function getAppliedEngines(): array;

    /**
     * Add applied engine
     */
    public function addAppliedEngine(string $engineId, array $result): self;

    /**
     * Get applied engine results
     */
    public function getEngineResult(string $engineId): ?array;

    /**
     * Get all engine results
     */
    public function getAllEngineResults(): array;

    /**
     * Get price history through engines
     */
    public function getPriceHistory(): array;

    /**
     * Add price history entry
     */
    public function addPriceHistoryEntry(string $engineId, float $price, array $details): self;

    /**
     * Get execution log
     */
    public function getExecutionLog(): array;

    /**
     * Add log entry
     */
    public function addLogEntry(string $message, string $level = 'info'): self;
}
