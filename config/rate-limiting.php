<?php

declare(strict_types=1);

/**
 * Rate Limiting Configuration
 *
 * Configure rate limits for different API endpoints and user actions.
 */

return [
    // Global rate limit settings
    'enabled' => true,
    'driver' => 'cache',
    'default_limit' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    'window_seconds' => 60,

    // Rate limits by endpoint pattern
    'limits' => [
        // Authentication endpoints (strict limits)
        'auth.login' => '5:1',          // 5 attempts per minute
        'auth.register' => '3:1',       // 3 registrations per minute
        'auth.password-reset' => '3:1', // 3 password resets per minute
        'auth.verify-email' => '5:1',   // 5 verifications per minute

        // API endpoints (moderate limits)
        'api.channels' => '60:1',
        'api.products' => '60:1',
        'api.orders' => '60:1',
        'api.users' => '60:1',

        // Search endpoints (higher limits)
        'api.search' => '200:1',

        // Webhook endpoints (no limits for trusted sources)
        'webhooks.stripe' => 'unlimited',
        'webhooks.whatsapp' => 'unlimited',
        'webhooks.telegram' => 'unlimited',

        // File upload endpoints
        'api.files.upload' => '10:1',   // 10 uploads per minute

        // AI generation endpoints (lower limits)
        'api.ai.generate-content' => '5:1',
        'api.ai.generate-image' => '3:1',
        'api.ai.speech-synthesis' => '10:1',

        // Export endpoints
        'api.export' => '5:1',
    ],

    // By-key strategies
    'by_key' => [
        // IP-based limiting
        'ip' => [
            'enabled' => true,
            'header' => 'X-Forwarded-For',
        ],

        // User-based limiting
        'user' => [
            'enabled' => true,
            'multiplier' => 2.0, // Authenticated users get 2x limit
        ],

        // API-key based limiting
        'api_key' => [
            'enabled' => true,
            'multiplier' => 3.0, // API key users get 3x limit
        ],
    ],

    // Response configuration
    'response' => [
        'status_code' => 429,
        'headers' => [
            'X-RateLimit-Limit' => 'limit',
            'X-RateLimit-Remaining' => 'remaining',
            'X-RateLimit-Reset' => 'reset',
            'Retry-After' => 'retry_after',
        ],
        'message' => 'Too many requests. Please try again later.',
    ],

    // Middleware configuration
    'middleware' => [
        'enabled' => true,
        'routes' => ['api/*', 'auth/*'],
        'exclude' => ['api/health', 'api/status'],
    ],

    // Whitelist configuration
    'whitelist' => [
        'ips' => explode(',', env('RATE_LIMIT_WHITELIST_IPS', '')),
        'users' => explode(',', env('RATE_LIMIT_WHITELIST_USERS', '')),
    ],

    // Cache configuration
    'cache' => [
        'store' => 'cache',
        'prefix' => 'rate_limit:',
        'ttl' => 3600,
    ],
];
