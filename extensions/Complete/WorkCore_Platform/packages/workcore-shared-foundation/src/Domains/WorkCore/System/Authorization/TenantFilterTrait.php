<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Authorization;

use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

trait TenantFilterTrait
{
    protected TenantContextContract $tenantContext;

    public function setTenantContext(TenantContextContract $context): self
    {
        $this->tenantContext = $context;
        return $this;
    }

    protected function ensureTenantContext(): TenantContextContract
    {
        if (!isset($this->tenantContext)) {
            throw new RuntimeException(
                static::class . ' requires TenantContext to be set. Call setTenantContext() first.'
            );
        }
        return $this->tenantContext;
    }

    protected function applyTenantFilter(Builder $query, string $tenantColumn = 'company_id'): Builder
    {
        $context = $this->ensureTenantContext();
        if ($context->hasTenant()) {
            $query->where($tenantColumn, $context->companyId());
        }
        return $query;
    }

    protected function withTenantScope(string $tenantColumn = 'company_id'): Builder
    {
        if (!method_exists($this, 'query')) {
            throw new RuntimeException(
                static::class . ' must extend a Model or have a query() method to use withTenantScope'
            );
        }

        return $this->applyTenantFilter($this->query(), $tenantColumn);
    }
}
