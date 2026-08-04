<?php

declare(strict_types=1);

namespace App\Extensions\AIChatPro\System\Integration\WorkCore;

use App\Domains\WorkCore\System\Authorization\CompanyRecordAuthorizer;
use App\Domains\WorkCore\System\Tenancy\TenantContext;
use App\Extensions\AIChatPro\System\Integration\Contracts\WorkCoreQueryRepository;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Base service for all WorkCore module queries in AiChatPro.
 * Provides tenant isolation, authorization, and consistent query patterns.
 */
abstract class BaseWorkCoreService implements WorkCoreQueryRepository
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected CompanyRecordAuthorizer $authorizer,
    ) {
        if (!$tenantContext->hasTenant()) {
            throw new RuntimeException('TenantContext must be initialized before using WorkCore services');
        }
    }

    public function query(string $query, array $parameters = []): mixed
    {
        // Override in subclasses for specific query implementations
        throw new RuntimeException("Query '{$query}' not implemented");
    }

    public function list(string $type, int $limit = 100, int $offset = 0): array
    {
        // Verify authorization
        if (!$this->authorize('read', $type)) {
            return [];
        }

        // Override in subclasses for specific implementations
        return [];
    }

    public function get(string $type, string $id): ?array
    {
        // Verify authorization
        if (!$this->authorize('read', $type)) {
            return null;
        }

        // Override in subclasses for specific implementations
        return null;
    }

    public function authorize(string $action, string $resource): bool
    {
        $tenantId = $this->tenantContext->companyId();
        return $this->authorizer->isAllowed($tenantId, $action, $resource);
    }

    /**
     * Apply tenant filter to query builder.
     */
    protected function applyTenantFilter(Builder $query): Builder
    {
        return $query->where('company_id', $this->tenantContext->companyId());
    }

    /**
     * Get current tenant ID.
     */
    protected function getTenantId(): int
    {
        return $this->tenantContext->companyId();
    }

    /**
     * Get current user ID if available.
     */
    protected function getUserId(): ?int
    {
        return $this->tenantContext->userId();
    }
}
