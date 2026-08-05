<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult,ActionRequest,PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts\PricingRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class UpsertPricingRule implements BusinessActionHandlerContract
{
    public function __construct(private PricingRepositoryContract $repository) {}
    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $rule = $this->repository->upsertRule($request->companyId, $request->actorId, $request->payload);
        return new ActionHandlerResult(
            data: $rule,
            aggregate: new TypedReference('pricing_rule', (string) $rule['public_id']),
            events: [new PendingDomainEvent('workcore.pricing.rule.upserted', 1, ['pricing_rule' => $rule])],
        );
    }
}
