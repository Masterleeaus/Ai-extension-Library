<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping;

abstract class ShippingProvider
{
    protected string $apiKey;
    protected string $apiSecret;
    protected bool $testMode = true;

    public function __construct(string $apiKey, string $apiSecret, bool $testMode = true)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testMode = $testMode;
    }

    abstract public function calculateRate(
        array $origin,
        array $destination,
        array $package,
        ?string $serviceType = null
    ): ShippingRate;

    abstract public function createShipment(
        array $origin,
        array $destination,
        array $package,
        array $items,
        ?string $serviceType = null
    ): ShippingResult;

    abstract public function getTracking(string $trackingNumber): TrackingInfo;

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function setTestMode(bool $testMode): void
    {
        $this->testMode = $testMode;
    }
}
