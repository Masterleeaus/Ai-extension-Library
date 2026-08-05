<?php

declare(strict_types=1);

/**
 * API Security Configuration
 *
 * Security settings for API endpoints, authentication, and authorization.
 */

return [
    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'default_limit' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
        'window' => 60, // seconds
        'by_key' => 'ip', // 'ip' or 'user_id'
    ],

    // Endpoint-specific rate limits
    'endpoint_limits' => [
        'auth.login' => 5,
        'auth.register' => 3,
        'auth.password-reset' => 3,
        'webhook.receive' => 1000,
        'api.*' => 60,
    ],

    // CORS Configuration
    'cors' => [
        'enabled' => true,
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8000')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'expose_headers' => ['X-Total-Count', 'X-Page-Count'],
        'max_age' => 86400,
        'supports_credentials' => true,
    ],

    // CSRF Protection
    'csrf' => [
        'enabled' => true,
        'exclude_paths' => [
            'api/*',
            'webhook/*',
        ],
    ],

    // API Key Authentication
    'api_keys' => [
        'enabled' => true,
        'header' => 'X-API-Key',
        'algorithm' => 'SHA256',
    ],

    // JWT Token Configuration
    'jwt' => [
        'enabled' => true,
        'algorithm' => 'HS256',
        'secret' => env('JWT_SECRET', base64_encode(env('APP_KEY'))),
        'ttl' => 3600, // 1 hour
        'refresh_ttl' => 604800, // 7 days
    ],

    // OAuth Configuration
    'oauth' => [
        'enabled' => false,
        'providers' => [
            'google' => [
                'client_id' => env('OAUTH_GOOGLE_CLIENT_ID'),
                'client_secret' => env('OAUTH_GOOGLE_CLIENT_SECRET'),
            ],
        ],
    ],

    // Input Validation
    'validation' => [
        'max_string_length' => 10000,
        'max_array_items' => 1000,
        'max_body_size' => 10485760, // 10MB
    ],

    // Security Headers
    'security_headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'",
    ],

    // IP Whitelisting
    'ip_whitelist' => [
        'enabled' => false,
        'ips' => [],
    ],

    // Suspicious Activity Detection
    'anomaly_detection' => [
        'enabled' => true,
        'threshold_failed_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'monitor_patterns' => true,
    ],
];
