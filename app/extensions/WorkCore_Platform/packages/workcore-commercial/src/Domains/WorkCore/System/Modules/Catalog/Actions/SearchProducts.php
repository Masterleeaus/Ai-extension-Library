<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SearchProducts
{
    public function __construct(
        private CatalogRepositoryContract $repository,
        private TenantContextContract $tenant,
    ) {}

    public function execute(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->repository->searchProducts($this->tenant->companyId(), $filters, $perPage);
    }
}
