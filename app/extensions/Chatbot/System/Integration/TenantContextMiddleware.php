<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Integration;

use App\Domains\WorkCore\System\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to resolve TenantContext for Chatbot PWA requests.
 * Ensures all conversational operations respect tenant boundaries.
 */
final class TenantContextMiddleware
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): mixed
    {
        // Resolve tenant from authenticated user or WebSocket context
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
        // PWA: resolve from user context, WebSocket, or header
        if ($request->header('X-Tenant-ID')) {
            return (int)$request->header('X-Tenant-ID');
        }

        $user = $request->user();
        if ($user && property_exists($user, 'company_id')) {
            return (int)$user->company_id;
        }

        return null;
    }

    private function resolveUserId(Request $request): ?int
    {
        $user = $request->user();
        return $user ? (int)$user->id : null;
    }
}
