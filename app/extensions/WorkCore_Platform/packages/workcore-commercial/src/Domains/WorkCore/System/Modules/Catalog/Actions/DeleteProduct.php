<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest, PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class DeleteProduct implements BusinessActionHandlerContract
{
    public function __construct(private CatalogRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $publicId = (string) $request->payload['product_public_id'];
        $this->repository->deleteProduct($publicId, $request->companyId);

        return new ActionHandlerResult(
            data: ['public_id' => $publicId],
            aggregate: new TypedReference('product', $publicId),
            events: [new PendingDomainEvent('workcore.catalog.product.deleted', 1, ['public_id' => $publicId])],
        );
    }
}
