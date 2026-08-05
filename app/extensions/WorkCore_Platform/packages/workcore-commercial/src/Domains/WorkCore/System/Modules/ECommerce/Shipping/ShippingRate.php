<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping;

class ShippingRate
{
    public function __construct(
        public string $serviceType,
        public string $serviceName,
        public float $rate,
        public string $currency = 'USD',
        public ?int $estimatedDays = null,
        public array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'service_type' => $this->serviceType,
            'service_name' => $this->serviceName,
            'rate' => $this->rate,
            'currency' => $this->currency,
            'estimated_days' => $this->estimatedDays,
            'metadata' => $this->metadata,
        ];
    }
}
