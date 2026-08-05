<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult,ActionRequest,PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts\PricingRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class RecordPricingSignal implements BusinessActionHandlerContract
{
    public function __construct(private PricingRepositoryContract $repository) {}
    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $signal = $this->repository->recordSignal($request->companyId, $request->actorId, $request->payload);
        return new ActionHandlerResult(
            data: $signal,
            aggregate: new TypedReference('pricing_signal', (string) $signal['public_id']),
            events: [new PendingDomainEvent('workcore.pricing.signal.recorded', 1, ['pricing_signal' => $signal])],
        );
    }
}
