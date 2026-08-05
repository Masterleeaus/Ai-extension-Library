<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping;

class TrackingInfo
{
    public function __construct(
        public string $trackingNumber,
        public string $status,
        public ?string $location = null,
        public ?\DateTime $lastUpdate = null,
        public ?string $estimatedDelivery = null,
        public array $events = [],
        public array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status,
            'location' => $this->location,
            'last_update' => $this->lastUpdate?->toIso8601String(),
            'estimated_delivery' => $this->estimatedDelivery,
            'events' => $this->events,
            'metadata' => $this->metadata,
        ];
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }
}
