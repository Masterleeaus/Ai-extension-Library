<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Context\TenantContextManager;
use App\Domains\Shared\Context\TenantContextImpl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Try to initialize context from request (sets auth user's tenant)
            if (auth()->check()) {
                TenantContextManager::initializeFromRequest($request);
            }
            // For webhook/service requests without auth, try header
            elseif ($request->hasHeader('X-Tenant-ID')) {
                $tenantId = $request->header('X-Tenant-ID');
                $context = TenantContextImpl::forService($tenantId, 'api-request');
                TenantContextManager::setContext($context);
            }
            // Unauthenticated request without tenant header - allow but log
            // Some routes might not require tenant context (health checks, etc)
        } catch (\Exception $e) {
            // Log but don't fail - some endpoints are public
            \Log::warning('Failed to set tenant context', ['error' => $e->getMessage()]);
        }

        try {
            $response = $next($request);
        } finally {
            // Always clear context after request
            TenantContextManager::clearContext();
        }

        return $response;
    }
}
