<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\DTO;

final readonly class PricingDecision
{
    /**
     * @param list<string> $appliedRuleIds
     * @param array<string,mixed> $factors
     */
    public function __construct(
        public int $basePriceMinor,
        public int $finalPriceMinor,
        public string $currency,
        public array $appliedRuleIds,
        public float $seasonalMultiplier,
        public float $occupancyMultiplier,
        public float $demandMultiplier,
        public float $demandScore,
        public string $demandLevel,
        public bool $minimumBoundApplied,
        public bool $maximumBoundApplied,
        public array $factors,
        public string $decisionHash,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'base_price_minor' => $this->basePriceMinor,
            'final_price_minor' => $this->finalPriceMinor,
            'currency' => $this->currency,
            'price_change_minor' => $this->finalPriceMinor - $this->basePriceMinor,
            'price_change_percent' => $this->basePriceMinor > 0
                ? round((($this->finalPriceMinor - $this->basePriceMinor) / $this->basePriceMinor) * 100, 4)
                : 0.0,
            'applied_rule_ids' => $this->appliedRuleIds,
            'seasonal_multiplier' => $this->seasonalMultiplier,
            'occupancy_multiplier' => $this->occupancyMultiplier,
            'demand_multiplier' => $this->demandMultiplier,
            'demand_score' => $this->demandScore,
            'demand_level' => $this->demandLevel,
            'minimum_bound_applied' => $this->minimumBoundApplied,
            'maximum_bound_applied' => $this->maximumBoundApplied,
            'factors' => $this->factors,
            'decision_hash' => $this->decisionHash,
        ];
    }
}
