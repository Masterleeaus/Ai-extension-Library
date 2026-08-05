<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Actions;

use App\Domains\WorkCore\System\Modules\Finance\Pricing\Services\PricingService;

final class PreviewDynamicPrice
{
    public function __construct(private PricingService $pricing) {}

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function __invoke(array $filters, int $companyId, int $perPage = 25): array
    {
        return $this->pricing->preview($companyId, $filters)->toArray();
    }
}
