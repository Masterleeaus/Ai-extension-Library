<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping;

class AustraliaPostProvider extends ShippingProvider
{
    private const AUSPOST_API_URL = 'https://api.auspost.com.au';

    public function calculateRate(
        array $origin,
        array $destination,
        array $package,
        ?string $serviceType = null
    ): ShippingRate {
        // Simulate Australia Post rate calculation
        $weight = $package['weight'] ?? 1;

        // Parcel Post rates for Australia
        $baseRate = match ($weight) {
            $w when $w <= 500 => 8.50,
            $w when $w <= 1000 => 12.50,
            $w when $w <= 2000 => 16.50,
            $w when $w <= 5000 => 22.50,
            default => 30.00,
        };

        $serviceName = match ($serviceType ?? 'parcel_post') {
            'express_post' => 'Australia Post Express Post',
            'parcel_post' => 'Australia Post Parcel Post',
            default => 'Australia Post Standard',
        };

        $estimatedDays = match ($serviceType ?? 'parcel_post') {
            'express_post' => 3,
            'parcel_post' => 5,
            default => 7,
        };

        return new ShippingRate(
            serviceType: $serviceType ?? 'parcel_post',
            serviceName: $serviceName,
            rate: $baseRate,
            currency: 'AUD',
            estimatedDays: $estimatedDays,
        );
    }

    public function createShipment(
        array $origin,
        array $destination,
        array $package,
        array $items,
        ?string $serviceType = null
    ): ShippingResult {
        // Simulate Australia Post shipment creation
        $shipmentId = 'AP-' . strtoupper(bin2hex(random_bytes(6)));
        $trackingNumber = 'AP' . str_pad(random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);

        return new ShippingResult(
            success: true,
            shipmentId: $shipmentId,
            trackingNumber: $trackingNumber,
            cost: $this->calculateRate($origin, $destination, $package, $serviceType)->rate,
            gatewayResponse: [
                'shipmentId' => $shipmentId,
                'trackingNumber' => $trackingNumber,
                'status' => 'created',
            ],
        );
    }

    public function getTracking(string $trackingNumber): TrackingInfo
    {
        // Simulate Australia Post tracking
        $statuses = ['accepted', 'in_transit', 'out_for_delivery', 'delivered'];
        $randomStatus = $statuses[array_rand($statuses)];

        return new TrackingInfo(
            trackingNumber: $trackingNumber,
            status: $randomStatus,
            location: 'Australia',
            lastUpdate: now(),
            estimatedDelivery: now()->addDays(5)->toDateString(),
            events: [
                [
                    'timestamp' => now()->toIso8601String(),
                    'status' => 'accepted',
                    'location' => 'Post Office',
                ],
                [
                    'timestamp' => now()->addHours(12)->toIso8601String(),
                    'status' => 'in_transit',
                    'location' => 'Delivery Hub',
                ],
            ],
        );
    }
}
