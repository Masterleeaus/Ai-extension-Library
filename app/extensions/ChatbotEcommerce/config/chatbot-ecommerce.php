<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('CHATBOT_ECOMMERCE_ENABLED', true),
    'search_cache_ttl_seconds' => (int) env('CHATBOT_ECOMMERCE_SEARCH_CACHE_TTL', 300),

    'marketplaces' => [
        'dispatch_mode' => env('CHATBOT_ECOMMERCE_MARKETPLACE_DISPATCH_MODE', 'async'),
        'providers' => [
            'amazon' => [
                'gateway_url' => env('CHATBOT_ECOMMERCE_AMAZON_GATEWAY_URL', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_URL')),
                'gateway_secret' => env('CHATBOT_ECOMMERCE_AMAZON_GATEWAY_SECRET', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_SECRET')),
                'connect_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_CONNECT_TIMEOUT', 5),
                'timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_TIMEOUT', 20),
                'write_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_TIMEOUT', 30),
                'retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_READ_RETRIES', 2),
                'write_retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_RETRIES', 1),
                'retry_delay_ms' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_RETRY_DELAY_MS', 250),
            ],
            'ebay' => [
                'gateway_url' => env('CHATBOT_ECOMMERCE_EBAY_GATEWAY_URL', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_URL')),
                'gateway_secret' => env('CHATBOT_ECOMMERCE_EBAY_GATEWAY_SECRET', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_SECRET')),
                'connect_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_CONNECT_TIMEOUT', 5),
                'timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_TIMEOUT', 20),
                'write_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_TIMEOUT', 30),
                'retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_READ_RETRIES', 2),
                'write_retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_RETRIES', 1),
                'retry_delay_ms' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_RETRY_DELAY_MS', 250),
            ],
            'etsy' => [
                'gateway_url' => env('CHATBOT_ECOMMERCE_ETSY_GATEWAY_URL', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_URL')),
                'gateway_secret' => env('CHATBOT_ECOMMERCE_ETSY_GATEWAY_SECRET', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_SECRET')),
                'connect_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_CONNECT_TIMEOUT', 5),
                'timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_TIMEOUT', 20),
                'write_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_TIMEOUT', 30),
                'retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_READ_RETRIES', 2),
                'write_retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_RETRIES', 1),
                'retry_delay_ms' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_RETRY_DELAY_MS', 250),
            ],
            'generic' => [
                'gateway_url' => env('CHATBOT_ECOMMERCE_GENERIC_GATEWAY_URL', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_URL')),
                'gateway_secret' => env('CHATBOT_ECOMMERCE_GENERIC_GATEWAY_SECRET', env('CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_SECRET')),
                'connect_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_CONNECT_TIMEOUT', 5),
                'timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_TIMEOUT', 20),
                'write_timeout_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_TIMEOUT', 30),
                'retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_READ_RETRIES', 2),
                'write_retries' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_RETRIES', 1),
                'retry_delay_ms' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_RETRY_DELAY_MS', 250),
            ],
        ],
    ],

    'customer_communications' => [
        'auto_reply_confidence' => (float) env('CHATBOT_ECOMMERCE_AUTO_REPLY_CONFIDENCE', 0.90),
        'human_handoff' => (bool) env('CHATBOT_ECOMMERCE_HUMAN_HANDOFF', true),
        'action_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_SUPPORT_ACTION_TTL', 15),
    ],

    'queues' => [
        'marketplace_read' => env('CHATBOT_ECOMMERCE_QUEUE_MARKETPLACE_READ', 'chatbot-ecommerce-marketplace-read'),
        'marketplace_write' => env('CHATBOT_ECOMMERCE_QUEUE_MARKETPLACE_WRITE', 'chatbot-ecommerce-marketplace-write'),
        'inventory_reconciliation' => env('CHATBOT_ECOMMERCE_QUEUE_INVENTORY', 'chatbot-ecommerce-inventory-reconciliation'),
        'payment_webhooks' => env('CHATBOT_ECOMMERCE_QUEUE_PAYMENT_WEBHOOKS', 'chatbot-ecommerce-payment-webhooks'),
    ],

    'shopify' => [
        'storefront_api_version' => env('CHATBOT_ECOMMERCE_SHOPIFY_STOREFRONT_API_VERSION', '2026-07'),
    ],
];
