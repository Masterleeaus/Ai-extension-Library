<?php

declare(strict_types=1);

/**
 * Database Performance Configuration
 *
 * Settings for query optimization, indexing, and connection pooling.
 */

return [
    // Connection pooling
    'pooling' => [
        'enabled' => true,
        'pool_size' => (int) env('DB_POOL_SIZE', 10),
        'pool_timeout' => (int) env('DB_POOL_TIMEOUT', 30),
        'pool_name' => 'default',
    ],

    // Query optimization
    'optimization' => [
        'enable_query_cache' => false,
        'enable_explain' => app()->isLocal(),
        'slow_query_threshold_ms' => 1000,
        'log_slow_queries' => true,
    ],

    // Eager loading
    'eager_loading' => [
        'enabled' => true,
        'default_relations' => [],
        'lazy_load_threshold' => 1000, // Items before lazy loading triggers
    ],

    // Indexing strategy
    'indexes' => [
        'primary_key' => 'id',
        'timestamps' => true,
        'soft_deletes' => true,

        // Composite indexes for common queries
        'composite' => [
            'company_id_user_id' => true,
            'company_id_status' => true,
            'created_at_company_id' => true,
        ],
    ],

    // Pagination
    'pagination' => [
        'default_per_page' => (int) env('API_PAGINATION_PER_PAGE', 15),
        'max_per_page' => 100,
        'use_cursor' => false,
    ],

    // Chunking configuration
    'chunking' => [
        'enabled' => true,
        'chunk_size' => 1000,
        'for_large_updates' => true,
        'for_large_deletes' => true,
    ],

    // N+1 Detection
    'n_plus_one_detection' => [
        'enabled' => app()->isLocal(),
        'threshold' => 10,
        'queries_to_track' => 50,
    ],

    // Connection pooling by database
    'databases' => [
        'mysql' => [
            'pool_size' => 10,
            'pool_timeout' => 30,
            'pool_acquire_timeout' => 5,
        ],
        'pgsql' => [
            'pool_size' => 15,
            'pool_timeout' => 30,
            'pool_acquire_timeout' => 5,
        ],
    ],

    // Query monitoring
    'monitoring' => [
        'enable_timing' => true,
        'enable_logging' => app()->isLocal(),
        'enable_metrics' => true,
        'metrics_backend' => 'prometheus',
    ],
];
