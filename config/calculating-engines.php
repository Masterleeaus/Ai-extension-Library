<?php

return [
    /*
     * Engine Registry Configuration
     *
     * Registers calculating engines for the WorkCore pricing calculation pipeline.
     * Engines are executed in priority order (lower = runs first).
     */

    'engines' => [
        'discount_engine' => [
            'enabled' => true,
            'priority' => 20,
            'class' => \App\Domains\WorkCore\Calculating\Engines\DiscountCalculatingEngine::class,
            'config' => [
                'enabled' => true,
                'allow_multiple_discounts' => false,
                'max_discount_percentage' => 100,
                'min_discount_amount' => 0,
            ],
        ],

        'dynamic_pricing' => [
            'enabled' => true,
            'priority' => 100,
            'class' => \App\Domains\WorkCore\Pricing\Engines\DynamicPricingEngine::class,
            'config' => [
                'enable_rules' => true,
                'enable_seasonal' => true,
                'enable_occupancy' => true,
                'enable_demand' => true,
                'min_price' => null,
                'max_price' => null,
                'supported_resource_types' => [],
            ],
        ],

        // Future engines can be registered here:
        // 'tax_engine' => [
        //     'enabled' => true,
        //     'priority' => 5,
        //     'class' => \App\Domains\WorkCore\Tax\Engines\TaxEngine::class,
        //     'config' => [...],
        // ],

        // 'loyalty_engine' => [
        //     'enabled' => true,
        //     'priority' => 25,
        //     'class' => \App\Domains\WorkCore\Loyalty\Engines\LoyaltyEngine::class,
        //     'config' => [...],
        // ],

        // 'promotion_engine' => [
        //     'enabled' => true,
        //     'priority' => 30,
        //     'class' => \App\Domains\WorkCore\Promotion\Engines\PromotionEngine::class,
        //     'config' => [...],
        // ],
    ],

    /*
     * Pipeline Configuration
     *
     * Configuration for the calculating engine pipeline execution
     */

    'pipeline' => [
        'stop_on_error' => false,
        'allow_conflicts' => false,
        'conflict_resolution' => 'maximum', // 'maximum', 'minimum', 'average', 'first', 'last'
    ],

    /*
     * Logging Configuration
     *
     * Enable detailed logging of engine execution
     */

    'logging' => [
        'enabled' => true,
        'channel' => 'calculating_engines',
        'log_level' => 'debug',
        'log_pipeline_results' => true,
        'log_conflicts' => true,
    ],

    /*
     * Cache Configuration
     *
     * Engine results can be cached to improve performance
     */

    'caching' => [
        'enabled' => false,
        'ttl' => 3600, // Cache for 1 hour
        'driver' => 'redis', // 'redis', 'database', or 'file'
    ],
];
