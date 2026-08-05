<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult,ActionRequest,PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts\PricingRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class UpsertSeasonalRate implements BusinessActionHandlerContract
{
    public function __construct(private PricingRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $rate = $this->repository->upsertSeasonalRate($request->companyId, $request->actorId, $request->payload);

        return new ActionHandlerResult(
            data: $rate,
            aggregate: new TypedReference('seasonal_rate', (string) $rate['public_id']),
            events: [new PendingDomainEvent('workcore.pricing.seasonal_rate.upserted', 1, ['seasonal_rate' => $rate])],
        );
    }
}
