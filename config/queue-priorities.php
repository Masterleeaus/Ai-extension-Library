<?php

declare(strict_types=1);

/**
 * Queue Priorities Configuration
 *
 * Defines job priority levels and timeout configurations for different job types.
 */

return [
    // Priority levels (higher = more important)
    'priorities' => [
        'critical' => 100,
        'high' => 50,
        'normal' => 0,
        'low' => -50,
    ],

    // Job timeout configurations
    'timeouts' => [
        // Critical jobs (must complete)
        'payment-processing' => 300,
        'webhook-delivery' => 120,
        'user-authentication' => 60,

        // High priority jobs
        'email-sending' => 60,
        'notification-delivery' => 45,
        'report-generation' => 600,

        // Normal priority jobs
        'data-sync' => 300,
        'cache-refresh' => 180,
        'search-indexing' => 240,

        // Low priority jobs
        'analytics-processing' => 900,
        'log-cleanup' => 600,
        'backup-creation' => 1800,
    ],

    // Retry configurations
    'retries' => [
        'critical' => 5,
        'high' => 3,
        'normal' => 2,
        'low' => 1,
    ],

    // Default retry delay in seconds
    'retry_delay' => [
        'immediate' => 0,
        'short' => 60,
        'medium' => 300,
        'long' => 900,
    ],

    // Job batching configuration
    'batching' => [
        'enabled' => true,
        'batch_size' => 100,
        'batch_timeout' => 3600,
    ],
];
