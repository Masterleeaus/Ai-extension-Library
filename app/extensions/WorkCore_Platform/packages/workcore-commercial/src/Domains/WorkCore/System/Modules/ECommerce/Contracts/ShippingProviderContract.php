<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Contracts;

use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping\ShippingRate;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping\ShippingResult;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping\TrackingInfo;

interface ShippingProviderContract
{
    public function calculateRate(
        array $origin,
        array $destination,
        array $package,
        ?string $serviceType = null
    ): ShippingRate;

    public function createShipment(
        array $origin,
        array $destination,
        array $package,
        array $items,
        ?string $serviceType = null
    ): ShippingResult;

    public function getTracking(string $trackingNumber): TrackingInfo;
}
