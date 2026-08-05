<?php

declare(strict_types=1);

/**
 * Pagination Configuration
 *
 * Default pagination settings for API and web responses.
 */

return [
    // Default pagination
    'default' => [
        'per_page' => (int) env('API_PAGINATION_PER_PAGE', 15),
        'path' => '/api',
        'query' => 'page',
    ],

    // Per-endpoint pagination settings
    'endpoints' => [
        'channels' => [
            'per_page' => 20,
            'max_per_page' => 100,
        ],
        'products' => [
            'per_page' => 24,
            'max_per_page' => 200,
        ],
        'orders' => [
            'per_page' => 25,
            'max_per_page' => 100,
        ],
        'users' => [
            'per_page' => 15,
            'max_per_page' => 50,
        ],
        'search' => [
            'per_page' => 10,
            'max_per_page' => 50,
        ],
    ],

    // Cursor-based pagination
    'cursor' => [
        'enabled' => false,
        'use_for_large_datasets' => true,
        'threshold' => 10000, // Switch to cursor pagination above this count
    ],

    // Offset-based pagination (traditional)
    'offset' => [
        'enabled' => true,
        'max_offset' => 5000, // Maximum starting position
    ],

    // Pagination view
    'view' => 'pagination::bootstrap-4',

    // Pagination bootstrap view (if used)
    'bootstrap' => [
        'path' => 'pagination::bootstrap-4',
        'items' => '5',
    ],

    // Query string parameter names
    'parameters' => [
        'page' => 'page',
        'per_page' => 'per_page',
        'sort' => 'sort',
        'direction' => 'direction',
        'search' => 'q',
    ],

    // Sorting configuration
    'sorting' => [
        'default_field' => 'created_at',
        'default_direction' => 'desc',
        'allowed_fields' => [],
        'forbidden_fields' => ['password', 'api_token', 'secret'],
    ],

    // Filtering configuration
    'filtering' => [
        'enabled' => true,
        'max_filters' => 10,
        'allowed_operators' => ['=', '!=', '>', '<', '>=', '<=', 'like', 'in'],
    ],
];
