<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListServicePackages
{
    public function __construct(
        private CatalogRepositoryContract $repository,
        private TenantContextContract $tenant,
    ) {}

    public function execute(int $perPage = 25): LengthAwarePaginator
    {
        return $this->repository->listServicePackages($this->tenant->companyId(), $perPage);
    }
}
