<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult,ActionRequest,PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Services\PricingService;
use App\Domains\WorkCore\System\References\TypedReference;

final class ApplyDynamicPrice implements BusinessActionHandlerContract
{
    public function __construct(private PricingService $pricing) {}
    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $record = $this->pricing->apply($request->companyId, $request->actorId, $request->payload);
        return new ActionHandlerResult(
            data: $record,
            aggregate: new TypedReference('price_history', (string) $record['public_id']),
            events: [new PendingDomainEvent('workcore.pricing.applied', 1, ['price_history' => $record])],
        );
    }
}
