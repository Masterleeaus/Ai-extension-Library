<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Services;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Models\AccommodationStay;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Models\RoomInventory;

class DynamicPricingService
{
    /**
     * Calculate dynamic price based on demand and seasonality
     */
    public function calculateDynamicPrice(RoomInventory $room, string $checkInDate, string $checkOutDate): float
    {
        $basePrice = $room->base_price ?? 0;
        $demandMultiplier = $this->calculateDemandMultiplier($room, $checkInDate, $checkOutDate);
        $seasonalityMultiplier = $this->calculateSeasonalityMultiplier($checkInDate);

        return round($basePrice * $demandMultiplier * $seasonalityMultiplier, 2);
    }

    /**
     * Calculate multiplier based on booking demand
     */
    private function calculateDemandMultiplier(RoomInventory $room, string $checkInDate, string $checkOutDate): float
    {
        $totalRooms = RoomInventory::where('company_id', $room->company_id)
            ->where('room_type', $room->room_type)
            ->count();

        $bookedRooms = AccommodationStay::where('room_inventory_id', $room->id)
            ->whereDate('check_in_date', '<=', $checkOutDate)
            ->whereDate('check_out_date', '>=', $checkInDate)
            ->count();

        if ($totalRooms === 0) {
            return 1.0;
        }

        $occupancyRate = $bookedRooms / $totalRooms;

        // Price increases as occupancy approaches 100%
        if ($occupancyRate >= 0.9) {
            return 1.5;
        } elseif ($occupancyRate >= 0.75) {
            return 1.3;
        } elseif ($occupancyRate >= 0.5) {
            return 1.1;
        } elseif ($occupancyRate <= 0.2) {
            return 0.8;
        }

        return 1.0;
    }

    /**
     * Calculate seasonality multiplier
     */
    private function calculateSeasonalityMultiplier(string $checkInDate): float
    {
        $month = date('m', strtotime($checkInDate));

        // Peak seasons: June-August, December
        if (in_array($month, ['06', '07', '08', '12'], true)) {
            return 1.4;
        }

        // High season: March-May, September-November
        if (in_array($month, ['03', '04', '05', '09', '10', '11'], true)) {
            return 1.15;
        }

        // Low season: January-February
        return 0.9;
    }

    /**
     * Get occupancy rate for date range
     */
    public function getOccupancyRate(RoomInventory $room, string $startDate, string $endDate): float
    {
        $totalRooms = RoomInventory::where('company_id', $room->company_id)
            ->where('room_type', $room->room_type)
            ->count();

        $bookedRooms = AccommodationStay::where('room_inventory_id', $room->id)
            ->whereDate('check_in_date', '<=', $endDate)
            ->whereDate('check_out_date', '>=', $startDate)
            ->count();

        return $totalRooms > 0 ? ($bookedRooms / $totalRooms) : 0.0;
    }
}
