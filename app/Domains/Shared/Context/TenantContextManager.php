<?php

namespace App\Domains\Shared\Context;

use Illuminate\Support\Facades\Auth;
use RuntimeException;

class TenantContextManager
{
    private static ?TenantContext $currentContext = null;

    /**
     * Set the current tenant context for this request
     */
    public static function setContext(TenantContext $context): void
    {
        self::$currentContext = $context;
    }

    /**
     * Get the current tenant context
     */
    public static function getContext(): TenantContext
    {
        if (self::$currentContext === null) {
            throw new RuntimeException('No tenant context set. Did you forget to set context in middleware?');
        }

        return self::$currentContext;
    }

    /**
     * Check if context is set
     */
    public static function hasContext(): bool
    {
        return self::$currentContext !== null;
    }

    /**
     * Get tenant ID from current context
     */
    public static function getTenantId(): string
    {
        return self::getContext()->getTenantId();
    }

    /**
     * Clear context (typically on logout or request end)
     */
    public static function clearContext(): void
    {
        self::$currentContext = null;
    }

    /**
     * Create context from request and set it
     */
    public static function initializeFromRequest(\Illuminate\Http\Request $request): TenantContext
    {
        $context = TenantContextImpl::fromRequest($request);
        self::setContext($context);
        return $context;
    }
}
