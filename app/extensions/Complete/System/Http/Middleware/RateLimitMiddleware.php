<?php

declare(strict_types=1);

namespace Extensions\Complete\System\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\RateLimiter\Limiter;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $limit = '60,1'): mixed
    {
        [$requests, $minutes] = explode(',', $limit);

        $key = $this->resolveKey($request);

        if (RateLimiter::tooManyAttempts($key, (int)$requests)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, (int)$minutes * 60);

        return $next($request);
    }

    private function resolveKey(Request $request): string
    {
        $ip = $request->ip();
        $path = $request->getPathInfo();
        $user = $request->user()?->id ?? 'guest';

        return "rate_limit:{$user}:{$ip}:{$path}";
    }
}
