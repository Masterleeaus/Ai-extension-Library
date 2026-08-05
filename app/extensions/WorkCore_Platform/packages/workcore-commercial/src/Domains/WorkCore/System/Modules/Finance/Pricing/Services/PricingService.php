<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Services;

use App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts\PricingRepositoryContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Domain\DynamicPriceCalculator;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\DTO\PricingDecision;
use DateTimeImmutable;

final class PricingService
{
    public function __construct(
        private PricingRepositoryContract $repository,
        private DynamicPriceCalculator $calculator,
        private DemandAnalysisService $demand,
    ) {}

    /** @param array<string,mixed> $input */
    public function preview(int $companyId, array $input): PricingDecision
    {
        $context = $this->repository->context($companyId, $input, new DateTimeImmutable());
        if (isset($input['demand_signals']) && is_array($input['demand_signals'])) {
            $context['demand_score'] = $this->demand->score($input['demand_signals']);
        }
        return $this->calculator->calculate($input + $context);
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function apply(int $companyId, int $actorId, array $input): array
    {
        $decision = $this->preview($companyId, $input);
        return $this->repository->recordDecision($companyId, $actorId, $input, $decision);
    }
}
