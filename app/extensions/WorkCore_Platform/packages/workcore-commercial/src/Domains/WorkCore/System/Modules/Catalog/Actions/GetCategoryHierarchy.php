<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;

final class GetCategoryHierarchy implements BusinessActionHandlerContract
{
    public function __construct(private CatalogRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $categoryPublicId = (string) $request->payload['category_public_id'];
        $hierarchy = $this->repository->getCategoryHierarchy($request->companyId, $categoryPublicId);

        return new ActionHandlerResult(data: $hierarchy);
    }
}
