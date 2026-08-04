<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Integration;

use App\Domains\WorkCore\System\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to resolve TenantContext for AIAgent operations.
 * Ensures all autonomous agent actions execute under the correct tenant boundary.
 */
final class TenantContextMiddleware
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): mixed
    {
        // Resolve tenant from agent context, authenticated user, or request parameter
        $tenantId = $this->resolveTenantId($request);
        $userId = $this->resolveUserId($request);

        if ($tenantId !== null) {
            $this->tenantContext->set($tenantId, $userId);
        }

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }

    private function resolveTenantId(Request $request): ?int
    {
        // Priority: route parameter, header, authenticated user, agent context
        if ($request->route('company_id')) {
            return (int)$request->route('company_id');
        }

        if ($request->header('X-Tenant-ID')) {
            return (int)$request->header('X-Tenant-ID');
        }

        $user = $request->user();
        if ($user && property_exists($user, 'company_id')) {
            return (int)$user->company_id;
        }

        // For background job workers, tenant ID should be in job payload
        return null;
    }

    private function resolveUserId(Request $request): ?int
    {
        $user = $request->user();
        return $user ? (int)$user->id : null;
    }
}
