<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest, PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class CreateServicePackage implements BusinessActionHandlerContract
{
    public function __construct(private CatalogRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $record = $this->repository->createServicePackage($request->payload, $request->companyId, $request->actorId);

        return new ActionHandlerResult(
            data: $record,
            aggregate: new TypedReference('service_package', (string) $record['public_id']),
            events: [new PendingDomainEvent('workcore.catalog.service_package.created', 1, ['record' => $record])],
        );
    }
}
