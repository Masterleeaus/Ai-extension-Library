<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dynamic Pricing Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the dynamic pricing engine and related services
    |
    */

    'dynamic_engine' => [
        'enable_rules' => env('PRICING_ENABLE_RULES', true),
        'enable_seasonal' => env('PRICING_ENABLE_SEASONAL', true),
        'enable_occupancy' => env('PRICING_ENABLE_OCCUPANCY', true),
        'enable_demand' => env('PRICING_ENABLE_DEMAND', true),
        'min_price' => env('PRICING_MIN_PRICE', null),
        'max_price' => env('PRICING_MAX_PRICE', null),
        'supported_resource_types' => env('PRICING_RESOURCE_TYPES', []),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demand Analysis Configuration
    |--------------------------------------------------------------------------
    */

    'demand_analysis' => [
        'booking_weight' => 0.40,
        'search_weight' => 0.30,
        'inquiry_weight' => 0.20,
        'cancellation_weight' => 0.10,
        'history_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Revenue Optimization Configuration
    |--------------------------------------------------------------------------
    */

    'revenue_optimization' => [
        'low_occupancy_threshold' => 30,
        'high_occupancy_threshold' => 70,
        'critical_occupancy_threshold' => 90,
        'forecast_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Occupancy Thresholds
    |--------------------------------------------------------------------------
    */

    'occupancy_pricing' => [
        'low_multiplier' => 0.85,
        'normal_low_multiplier' => 0.92,
        'normal_multiplier' => 1.0,
        'high_multiplier' => 1.08,
        'very_high_multiplier' => 1.15,
        'critical_multiplier' => 1.25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Demand Pricing Factors
    |--------------------------------------------------------------------------
    */

    'demand_pricing' => [
        'low_demand_multiplier' => 0.8,
        'normal_demand_multiplier' => 1.0,
        'high_demand_multiplier' => 1.2,
        'critical_demand_multiplier' => 1.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Price History & Audit
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'track_price_history' => true,
        'audit_log_retention_days' => 365,
        'log_all_calculations' => env('PRICING_AUDIT_LOG_ALL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Calculating Engine Framework
    |--------------------------------------------------------------------------
    */

    'engines' => [
        'stop_on_error' => false,
        'allow_conflicts' => false,
        'conflict_resolution' => 'maximum', // maximum, minimum, average, first, last
    ],
];
