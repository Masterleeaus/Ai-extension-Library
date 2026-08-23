<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscriptions Module Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Payment method for billing
    | Options: 'manual', 'stripe', 'paypal'
    */
    'payment_method' => env('SUBSCRIPTION_PAYMENT_METHOD', 'manual'),

    /*
    | Maximum retry attempts for failed payments
    */
    'max_retry_attempts' => env('SUBSCRIPTION_MAX_RETRIES', 3),

    /*
    | Initial backoff duration in minutes for payment retries
    */
    'retry_backoff_minutes' => env('SUBSCRIPTION_RETRY_BACKOFF', 5),

    /*
    | Default trial period in days
    */
    'default_trial_days' => env('SUBSCRIPTION_TRIAL_DAYS', 14),

    /*
    | Enable automatic billing cycle processing
    */
    'auto_billing' => env('SUBSCRIPTION_AUTO_BILLING', true),

    /*
    | Time (in hours) before billing date to process billing
    */
    'billing_advance_hours' => env('SUBSCRIPTION_BILLING_ADVANCE', 24),

    /*
    | Enable usage limit enforcement
    */
    'enforce_usage_limits' => env('SUBSCRIPTION_ENFORCE_LIMITS', true),

    /*
    | Features definition
    | This maps features to tiers
    */
    'features' => [
        'api_calls' => [
            'slug' => 'api_calls',
            'name' => 'API Calls',
            'description' => 'Monthly API call limit',
        ],
        'users' => [
            'slug' => 'users',
            'name' => 'Team Members',
            'description' => 'Maximum concurrent team members',
        ],
        'storage' => [
            'slug' => 'storage',
            'name' => 'Storage',
            'description' => 'Storage limit in GB',
        ],
        'concurrent_users' => [
            'slug' => 'concurrent_users',
            'name' => 'Concurrent Users',
            'description' => 'Maximum concurrent logged-in users',
        ],
        'api_access' => [
            'slug' => 'api_access',
            'name' => 'API Access',
            'description' => 'REST API access',
        ],
        'webhooks' => [
            'slug' => 'webhooks',
            'name' => 'Webhooks',
            'description' => 'Webhook support',
        ],
        'sso' => [
            'slug' => 'sso',
            'name' => 'Single Sign-On',
            'description' => 'SSO integration support',
        ],
        'custom_domain' => [
            'slug' => 'custom_domain',
            'name' => 'Custom Domain',
            'description' => 'Custom domain support',
        ],
    ],

    /*
    | Billing cycle configurations
    */
    'billing_cycles' => [
        'monthly' => ['days' => 30, 'name' => 'Monthly'],
        'quarterly' => ['days' => 90, 'name' => 'Quarterly'],
        'yearly' => ['days' => 365, 'name' => 'Yearly'],
    ],

    /*
    | Invoice configuration
    */
    'invoices' => [
        'payment_terms_days' => env('INVOICE_PAYMENT_TERMS', 30),
        'include_tax' => env('INVOICE_INCLUDE_TAX', false),
        'tax_rate' => env('INVOICE_TAX_RATE', 0),
    ],

    /*
    | Notifications
    */
    'notifications' => [
        'send_billing_reminders' => env('SEND_BILLING_REMINDERS', true),
        'send_payment_confirmations' => env('SEND_PAYMENT_CONFIRMATIONS', true),
        'send_renewal_notifications' => env('SEND_RENEWAL_NOTIFICATIONS', true),
        'send_overdue_notifications' => env('SEND_OVERDUE_NOTIFICATIONS', true),
    ],
];
