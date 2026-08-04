<?php

declare(strict_types=1);

namespace App\Extensions\AIChatPro\System\Integration\Contracts;

/**
 * Base contract for all WorkCore query repositories in AiChatPro.
 * Ensures tenant-safe queries, authorization, and consistent patterns.
 */
interface WorkCoreQueryRepository
{
    /**
     * Execute a query within the current tenant context.
     * Automatically applies tenant filters to results.
     *
     * @param  string  $query Query name (e.g., 'GetCustomerProfile')
     * @param  array  $parameters Query parameters
     * @return mixed Query result, filtered to current tenant
     */
    public function query(string $query, array $parameters = []): mixed;

    /**
     * List all records of a type within the current tenant.
     */
    public function list(string $type, int $limit = 100, int $offset = 0): array;

    /**
     * Get a single record by ID, verifying tenant ownership.
     */
    public function get(string $type, string $id): ?array;

    /**
     * Check if current user has permission for an action.
     */
    public function authorize(string $action, string $resource): bool;
}
