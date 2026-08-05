<?php

namespace App\Domains\WorkCore\Pricing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domains\WorkCore\Pricing\Services\PricingService;
use App\Domains\WorkCore\Pricing\Models\PricingRule;

class UpdatePricingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    protected string $companyId;

    protected string $resourceType;

    protected int $resourceId;

    protected float $basePrice;

    public function __construct(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ) {
        $this->companyId = $companyId;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->basePrice = $basePrice;
    }

    public function handle(PricingService $pricingService): void
    {
        // Calculate new price with all applicable rules
        $result = $pricingService->calculatePrice(
            $this->companyId,
            $this->basePrice,
            [
                'type' => $this->resourceType,
                'id' => $this->resourceId,
            ]
        );

        // If price changed, trigger price update event
        if ($result['final_price'] !== $this->basePrice) {
            event(new \App\Domains\WorkCore\Pricing\Events\PriceUpdated(
                $this->companyId,
                $this->resourceType,
                $this->resourceId,
                $this->basePrice,
                $result['final_price'],
                $result['engine_results'] ?? []
            ));
        }
    }
}
