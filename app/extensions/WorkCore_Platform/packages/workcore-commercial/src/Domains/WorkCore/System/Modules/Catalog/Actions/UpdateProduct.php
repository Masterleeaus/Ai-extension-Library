<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest, PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class UpdateProduct implements BusinessActionHandlerContract
{
    public function __construct(private CatalogRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $publicId = (string) $request->payload['product_public_id'];
        $record = $this->repository->updateProduct($publicId, $request->payload, $request->companyId, $request->actorId);

        return new ActionHandlerResult(
            data: $record,
            aggregate: new TypedReference('product', $publicId),
            events: [new PendingDomainEvent('workcore.catalog.product.updated', 1, ['record' => $record])],
        );
    }
}
