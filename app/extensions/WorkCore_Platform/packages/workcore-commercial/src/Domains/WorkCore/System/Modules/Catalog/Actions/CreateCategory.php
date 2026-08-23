<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest, PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class CreateCategory implements BusinessActionHandlerContract
{
    public function __construct(private CatalogRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $record = $this->repository->createCategory($request->payload, $request->companyId);

        return new ActionHandlerResult(
            data: $record,
            aggregate: new TypedReference('category', (string) $record['public_id']),
            events: [new PendingDomainEvent('workcore.catalog.category.created', 1, ['record' => $record])],
        );
    }
}
