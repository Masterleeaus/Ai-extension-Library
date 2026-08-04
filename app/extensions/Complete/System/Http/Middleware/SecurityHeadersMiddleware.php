<?php

declare(strict_types=1);

namespace Extensions\Complete\System\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($this->isSecure($request)) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }

    private function isSecure(Request $request): bool
    {
        return $request->secure() || app()->environment('production');
    }
}
