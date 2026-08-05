<?php

declare(strict_types=1);

/**
 * Cache Strategy Configuration
 *
 * Defines caching strategies for different data types and use cases.
 */

return [
    // Default cache strategy
    'default' => 'standard',

    'strategies' => [
        'standard' => [
            'driver' => 'file',
            'ttl' => 3600,
            'prefix' => 'cache_',
        ],

        'short' => [
            'driver' => 'file',
            'ttl' => 300,
            'prefix' => 'cache_short_',
        ],

        'long' => [
            'driver' => 'file',
            'ttl' => 86400,
            'prefix' => 'cache_long_',
        ],

        'permanent' => [
            'driver' => 'file',
            'ttl' => 31536000, // 1 year
            'prefix' => 'cache_permanent_',
        ],
    ],

    // Cache rules by entity type
    'entity_rules' => [
        'user' => 'short',
        'channel' => 'standard',
        'product' => 'standard',
        'settings' => 'long',
        'config' => 'permanent',
        'knowledge_document' => 'long',
        'company_metrics' => 'short',
    ],

    // Tag-based cache invalidation
    'tags' => [
        'users' => 'user:*',
        'channels' => 'channel:*',
        'products' => 'product:*',
        'company' => 'company:*',
        'permissions' => 'permission:*',
    ],
];
