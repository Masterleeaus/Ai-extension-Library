<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('CHATBOT_ECOMMERCE_ENABLED', true),
    'search_cache_ttl_seconds' => (int) env('CHATBOT_ECOMMERCE_SEARCH_CACHE_TTL', 300),

    'shopify' => [
        'storefront_api_version' => env('CHATBOT_ECOMMERCE_SHOPIFY_STOREFRONT_API_VERSION', '2026-07'),
    ],

    'marketplaces' => [
        'dispatch_mode' => env('CHATBOT_ECOMMERCE_MARKETPLACE_DISPATCH_MODE', 'async'),
        'search_cache_ttl_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_SEARCH_CACHE_TTL', 300),
        'maximum_results_per_provider' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_MAX_RESULTS', 50),
        'inventory_reconciliation' => [
            'maximum_sync_age_seconds' => (int) env('CHATBOT_ECOMMERCE_INVENTORY_MAX_SYNC_AGE', 300),
        ],
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
        'write' => [
            'dispatch_mode' => env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_DISPATCH_MODE', 'async'),
            'marketplace_write_mode' => env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_MODE', 'approval_only'),
            'require_approval' => (bool) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_REQUIRE_APPROVAL', true),
            'approval_secret' => env('CHATBOT_ECOMMERCE_MARKETPLACE_APPROVAL_SECRET', ''),
            'approval_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_APPROVAL_TTL', 10),
            'maximum_snapshot_age_seconds' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_MAX_SNAPSHOT_AGE', 300),
            'maximum_writes_per_minute' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_WRITES_PER_MINUTE', 10),
            'allow_inventory_oversell' => (bool) env('CHATBOT_ECOMMERCE_MARKETPLACE_ALLOW_OVERSELL', false),
            'allow_bulk_writes' => (bool) env('CHATBOT_ECOMMERCE_MARKETPLACE_ALLOW_BULK_WRITES', false),
            'allowed_operations' => [],
            'bulk' => [
                'maximum_items' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_BULK_MAX_ITEMS', 250),
                'approval_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_MARKETPLACE_BULK_APPROVAL_TTL', 15),
            ],
        ],
    ],

    'security' => [
        'session_authority_secret' => env('CHATBOT_ECOMMERCE_SESSION_AUTHORITY_SECRET', ''),
        'session_authority_ttl_seconds' => (int) env('CHATBOT_ECOMMERCE_SESSION_AUTHORITY_TTL', 1800),
    ],

    'conversation_context' => [
        'ttl_hours' => (int) env('CHATBOT_ECOMMERCE_CONTEXT_TTL_HOURS', 24),
        'maximum_pending_actions' => (int) env('CHATBOT_ECOMMERCE_MAX_PENDING_ACTIONS', 20),
    ],

    'customer_communications' => [
        'auto_reply_enabled' => (bool) env('CHATBOT_ECOMMERCE_AUTO_REPLY_ENABLED', false),
        'auto_reply_confidence' => (float) env('CHATBOT_ECOMMERCE_AUTO_REPLY_CONFIDENCE', 0.90),
        'automatic_refund_limit' => (int) env('CHATBOT_ECOMMERCE_AUTO_REFUND_LIMIT', 0),
        'automatic_discount_limit' => (int) env('CHATBOT_ECOMMERCE_AUTO_DISCOUNT_LIMIT', 0),
        'automatic_store_credit_limit' => (int) env('CHATBOT_ECOMMERCE_AUTO_STORE_CREDIT_LIMIT', 0),
        'automatic_replacement_limit' => (int) env('CHATBOT_ECOMMERCE_AUTO_REPLACEMENT_LIMIT', 0),
        'automatic_cancellation_limit' => (int) env('CHATBOT_ECOMMERCE_AUTO_CANCELLATION_LIMIT', 0),
        'currency' => env('CHATBOT_ECOMMERCE_COMMUNICATION_CURRENCY', 'AUD'),
        'human_handoff' => (bool) env('CHATBOT_ECOMMERCE_HUMAN_HANDOFF', true),
        'action_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_SUPPORT_ACTION_TTL', 15),
    ],

    'orders' => [
        'return_window_days' => (int) env('CHATBOT_ECOMMERCE_RETURN_WINDOW_DAYS', 30),
    ],

    'checkout' => [
        'delivery_options' => [],
        'allow_client_shipping_quotes' => (bool) env('CHATBOT_ECOMMERCE_ALLOW_CLIENT_SHIPPING_QUOTES', false),
        'ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_CHECKOUT_TTL_MINUTES', 30),
        'approval_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_CHECKOUT_APPROVAL_TTL', 15),
        'payment_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_PAYMENT_TTL_MINUTES', 30),
    ],

    'pricing' => [
        'prices_include_tax' => (bool) env('CHATBOT_ECOMMERCE_PRICES_INCLUDE_TAX', false),
        'tax_rates' => [],
        'default_tax_rate_bps' => (int) env('CHATBOT_ECOMMERCE_DEFAULT_TAX_RATE_BPS', 0),
        'shipping_tax_rate_bps' => (int) env('CHATBOT_ECOMMERCE_SHIPPING_TAX_RATE_BPS', 0),
        'coupon_reservation_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_COUPON_RESERVATION_TTL', 30),
    ],

    'shipping' => [
        'quote_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_SHIPPING_QUOTE_TTL', 15),
    ],

    'fulfillment' => [
        'require_tracking_for_delivery' => (bool) env('CHATBOT_ECOMMERCE_REQUIRE_TRACKING_FOR_DELIVERY', false),
    ],

    'payments' => [
        'allowed_methods' => ['bank_transfer', 'payid', 'cash', 'external'],
        'instructions' => [
            'bank_transfer' => [],
            'payid' => [],
            'cash' => [],
            'external' => [],
        ],
        'intent_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_PAYMENT_INTENT_TTL', 30),
        'webhook_tolerance_seconds' => (int) env('CHATBOT_ECOMMERCE_PAYMENT_WEBHOOK_TOLERANCE', 300),
        'providers' => [
            'internal' => [
                'webhook_secret' => env('CHATBOT_ECOMMERCE_PAYMENT_WEBHOOK_SECRET', ''),
            ],
        ],
    ],

    'bnpl' => [
        'require_licensed_provider' => (bool) env('CHATBOT_ECOMMERCE_BNPL_REQUIRE_LICENSED_PROVIDER', true),
        'maximum_installments' => (int) env('CHATBOT_ECOMMERCE_BNPL_MAX_INSTALLMENTS', 24),
        'offer_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_BNPL_OFFER_TTL', 15),
        'state_secret' => env('CHATBOT_ECOMMERCE_BNPL_STATE_SECRET', ''),
        'providers' => [],
        'rental_hire' => [],
    ],

    'rental_hire' => [
        'charge_generation_horizon_days' => (int) env('CHATBOT_ECOMMERCE_RENTAL_CHARGE_HORIZON_DAYS', 30),
        'maximum_generation_periods' => (int) env('CHATBOT_ECOMMERCE_RENTAL_MAX_GENERATION_PERIODS', 500),
        'allowed_payment_methods' => ['bank_transfer', 'payid', 'cash', 'external'],
        'payment_instructions' => [
            'bank_transfer' => [],
            'payid' => [],
            'cash' => [],
            'external' => [],
        ],
        'payment_request_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_RENTAL_PAYMENT_TTL', 1440),
        'account_number_prefix' => env('CHATBOT_ECOMMERCE_RENTAL_ACCOUNT_PREFIX', 'RNT'),
        'receipt_number_prefix' => env('CHATBOT_ECOMMERCE_RENTAL_RECEIPT_PREFIX', 'RCP'),
    ],

    'order_workbench' => [
        'fulfillment_delay_seconds' => (int) env('CHATBOT_ECOMMERCE_FULFILLMENT_DELAY_SECONDS', 172800),
        'proposal_ttl_minutes' => (int) env('CHATBOT_ECOMMERCE_ORDER_PROPOSAL_TTL', 30),
    ],

    'listing_intelligence' => [
        'enabled' => (bool) env('CHATBOT_ECOMMERCE_LISTING_INTELLIGENCE_ENABLED', true),
    ],

    'reliability' => [
        'webhooks' => [
            'max_attempts' => (int) env('CHATBOT_ECOMMERCE_WEBHOOK_MAX_ATTEMPTS', 5),
            'base_backoff_seconds' => (int) env('CHATBOT_ECOMMERCE_WEBHOOK_BASE_BACKOFF', 5),
            'maximum_backoff_seconds' => (int) env('CHATBOT_ECOMMERCE_WEBHOOK_MAX_BACKOFF', 300),
        ],
        'circuit_breaker' => [
            'failure_threshold' => (int) env('CHATBOT_ECOMMERCE_CIRCUIT_FAILURE_THRESHOLD', 5),
            'cooldown_seconds' => (int) env('CHATBOT_ECOMMERCE_CIRCUIT_COOLDOWN', 120),
        ],
    ],

    'queues' => [
        'marketplace_read' => env('CHATBOT_ECOMMERCE_QUEUE_MARKETPLACE_READ', 'chatbot-ecommerce-marketplace-read'),
        'marketplace_write' => env('CHATBOT_ECOMMERCE_QUEUE_MARKETPLACE_WRITE', 'chatbot-ecommerce-marketplace-write'),
        'inventory_reconciliation' => env('CHATBOT_ECOMMERCE_QUEUE_INVENTORY', 'chatbot-ecommerce-inventory-reconciliation'),
        'payment_webhooks' => env('CHATBOT_ECOMMERCE_QUEUE_PAYMENT_WEBHOOKS', 'chatbot-ecommerce-payment-webhooks'),
    ],

    'feature_flags' => [],
];
