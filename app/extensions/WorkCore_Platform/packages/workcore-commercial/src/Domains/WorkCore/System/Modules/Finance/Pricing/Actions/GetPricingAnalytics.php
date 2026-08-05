<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Actions;

use App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts\PricingRepositoryContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Services\RevenueOptimizationService;

final class GetPricingAnalytics
{
    public function __construct(private PricingRepositoryContract $repository, private RevenueOptimizationService $optimizer) {}

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function __invoke(array $filters, int $companyId, int $perPage = 25): array
    {
        $analytics = $this->repository->analytics($companyId, $filters);
        $analytics['recommendation'] = $this->optimizer->recommendation($analytics);
        return $analytics;
    }
}
