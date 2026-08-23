<?php

declare(strict_types=1);

return [
    'enabled' => env('WORKCORE_PRICING_ENABLED', true),
    'routes_enabled' => env('WORKCORE_PRICING_ROUTES_ENABLED', true),
    'route_prefix' => env('WORKCORE_PRICING_ROUTE_PREFIX', 'api/v1/workcore/pricing'),
    'permissions' => [
        'view' => env('WORKCORE_PRICING_VIEW_PERMISSION', 'money.view'),
        'analytics' => env('WORKCORE_PRICING_ANALYTICS_PERMISSION', 'money.reports.view'),
        'manage_rules' => env('WORKCORE_PRICING_RULE_PERMISSION', 'money.settings.manage'),
        'record_signals' => env('WORKCORE_PRICING_SIGNAL_PERMISSION', 'money.integrations.manage'),
        'apply' => env('WORKCORE_PRICING_APPLY_PERMISSION', 'money.quotes.draft'),
    ],
];
