<?php

declare(strict_types=1);

/**
 * Observability Configuration
 *
 * Settings for logging, tracing, metrics, and monitoring.
 */

return [
    // Logging
    'logging' => [
        'enabled' => true,
        'channels' => [
            'stack',
            'single',
            'structured',
        ],
        'structured_logging' => true,
        'json_format' => true,
        'include_context' => true,
    ],

    // Structured logging context
    'context' => [
        'correlation_id' => true,
        'request_id' => true,
        'user_id' => true,
        'company_id' => true,
        'session_id' => true,
        'ip_address' => true,
        'user_agent' => false, // Too verbose
    ],

    // Log levels
    'levels' => [
        'critical' => 500,
        'error' => 400,
        'warning' => 300,
        'notice' => 250,
        'info' => 200,
        'debug' => 100,
    ],

    // Tracing configuration
    'tracing' => [
        'enabled' => (bool) env('ENABLE_TRACING', false),
        'driver' => env('TRACING_DRIVER', 'jaeger'),
        'sample_rate' => (float) env('TRACING_SAMPLE_RATE', 0.1),
        'exporters' => [
            'jaeger' => [
                'endpoint' => env('JAEGER_ENDPOINT', 'http://localhost:14268/api/traces'),
                'service_name' => env('APP_NAME', 'ai-extensions'),
            ],
            'zipkin' => [
                'endpoint' => env('ZIPKIN_ENDPOINT', 'http://localhost:9411/api/v2/spans'),
            ],
        ],
    ],

    // Metrics configuration
    'metrics' => [
        'enabled' => (bool) env('ENABLE_METRICS', false),
        'driver' => env('METRICS_DRIVER', 'prometheus'),
        'prefix' => env('METRICS_PREFIX', 'app_'),
        'exporters' => [
            'prometheus' => [
                'endpoint' => env('PROMETHEUS_PUSHGATEWAY', 'http://localhost:9091'),
                'job' => env('APP_NAME', 'ai-extensions'),
            ],
        ],
    ],

    // Error tracking (Sentry)
    'error_tracking' => [
        'enabled' => (bool) env('SENTRY_DSN'),
        'dsn' => env('SENTRY_DSN'),
        'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
        'trace_sample_rate' => 0.1,
        'profiles_sample_rate' => 0.1,
        'integrations' => [
            'laravel' => true,
            'http_client' => true,
            'database' => true,
            'queue' => true,
        ],
    ],

    // Performance monitoring
    'performance' => [
        'enabled' => (bool) env('ENABLE_PERFORMANCE_MONITORING', false),
        'track_database_queries' => true,
        'track_http_requests' => true,
        'track_queue_jobs' => true,
        'slow_query_threshold_ms' => 1000,
        'slow_request_threshold_ms' => 5000,
    ],

    // Health checks
    'health' => [
        'enabled' => true,
        'endpoint' => '/api/health',
        'checks' => [
            'database' => true,
            'cache' => true,
            'queue' => true,
            'redis' => false,
            'disk_space' => true,
        ],
    ],

    // Audit logging
    'audit' => [
        'enabled' => true,
        'log_user_actions' => true,
        'log_admin_actions' => true,
        'log_data_access' => true,
        'log_data_changes' => true,
        'retention_days' => 90,
    ],
];
