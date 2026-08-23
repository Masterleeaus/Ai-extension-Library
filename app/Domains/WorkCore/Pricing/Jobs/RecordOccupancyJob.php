<?php

namespace App\Domains\WorkCore\Pricing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domains\WorkCore\Pricing\Services\PricingService;

class RecordOccupancyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    protected string $companyId;

    protected string $resourceType;

    protected int $resourceId;

    protected int $currentOccupancy;

    protected int $capacity;

    protected int $reservedUnits;

    protected int $pendingBookings;

    public function __construct(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $currentOccupancy,
        int $capacity,
        int $reservedUnits = 0,
        int $pendingBookings = 0
    ) {
        $this->companyId = $companyId;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->currentOccupancy = $currentOccupancy;
        $this->capacity = $capacity;
        $this->reservedUnits = $reservedUnits;
        $this->pendingBookings = $pendingBookings;
    }

    public function handle(PricingService $pricingService): void
    {
        // Record occupancy
        $occupancy = $pricingService->recordOccupancy(
            $this->companyId,
            $this->resourceType,
            $this->resourceId,
            $this->currentOccupancy,
            $this->capacity,
            $this->reservedUnits,
            $this->pendingBookings
        );

        // Dispatch pricing update job
        UpdatePricingJob::dispatch(
            $this->companyId,
            $this->resourceType,
            $this->resourceId,
            0 // Will fetch current base price from resource
        )->onQueue('pricing');
    }
}
