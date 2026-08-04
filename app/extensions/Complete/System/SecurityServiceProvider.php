<?php

declare(strict_types=1);

namespace Extensions\Complete\System;

use Extensions\Complete\System\Http\Middleware\RateLimitMiddleware;
use Extensions\Complete\System\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function boot(Kernel $kernel): void
    {
        $kernel->pushMiddleware(SecurityHeadersMiddleware::class);
    }

    public function register(): void
    {
        $this->app['router']->aliasMiddleware('rate-limit', RateLimitMiddleware::class);
    }
}
