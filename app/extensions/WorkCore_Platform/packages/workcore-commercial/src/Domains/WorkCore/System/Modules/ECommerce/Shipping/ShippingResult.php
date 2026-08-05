<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping;

class ShippingResult
{
    public function __construct(
        public bool $success,
        public ?string $shipmentId = null,
        public ?string $trackingNumber = null,
        public ?float $cost = null,
        public ?string $label = null,
        public ?string $error = null,
        public array $gatewayResponse = [],
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'shipment_id' => $this->shipmentId,
            'tracking_number' => $this->trackingNumber,
            'cost' => $this->cost,
            'label' => $this->label,
            'error' => $this->error,
            'gateway_response' => $this->gatewayResponse,
        ];
    }
}
