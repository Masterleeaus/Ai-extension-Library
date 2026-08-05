<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;

final class GetProductMedia implements BusinessActionHandlerContract
{
    public function __construct(private CatalogRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $productPublicId = (string) $request->payload['product_public_id'];
        $media = $this->repository->getProductMedia($productPublicId, $request->companyId);

        return new ActionHandlerResult(data: $media);
    }
}
