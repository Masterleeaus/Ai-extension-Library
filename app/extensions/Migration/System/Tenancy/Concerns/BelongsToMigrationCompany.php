<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Tenancy\Concerns;

use App\Extensions\Migration\System\Tenancy\MigrationTenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

trait BelongsToMigrationCompany
{
    protected static function bootBelongsToMigrationCompany(): void
    {
        static::addGlobalScope('migration_company', function (Builder $builder): void {
            $companyId = app(MigrationTenantResolver::class)->companyId();

            if ($companyId !== null) {
                $builder->where($builder->getModel()->qualifyColumn('company_id'), $companyId);
            }
        });

        static::creating(function ($model): void {
            if ($model->getAttribute('company_id') !== null) {
                return;
            }

            $companyId = app(MigrationTenantResolver::class)->companyId();

            if ($companyId === null) {
                throw new RuntimeException('Cannot create Migration Engine record without an active company context.');
            }

            $model->setAttribute('company_id', $companyId);
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScope('migration_company')->where('company_id', $companyId);
    }

    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('migration_company');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\WorkCore\System\Models\Company::class, 'company_id');
    }
}
