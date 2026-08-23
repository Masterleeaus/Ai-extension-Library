<?php

namespace App\Domains\MultiTenant\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-tenant trait for company isolation
 * Automatically scopes queries to current company and includes company_id, user_id, team_id fields
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Scope all queries to current company by default
        static::addGlobalScope('company_id', function (Builder $builder) {
            if ($companyId = auth()->user()?->company_id ?? config('app.default_company_id')) {
                $builder->where('company_id', $companyId);
            }
        });
    }

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTeam(Builder $query, $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    protected function getFillableAttributes(): array
    {
        return array_merge($this->fillable ?? [], ['company_id', 'user_id', 'team_id']);
    }
}
