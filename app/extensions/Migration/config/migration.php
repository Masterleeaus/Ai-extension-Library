<?php

return [
    'version' => 2.0,

    // Optional fallback for console/system contexts. Queued migration work should
    // prefer MigrationTenantContext so the company boundary is explicit.
    'default_company_id' => env('MIGRATION_DEFAULT_COMPANY_ID'),

    'permissions' => [
        'view' => 'migration.view',
        'configure' => 'migration.configure',
        'approve' => 'migration.approve',
        'execute' => 'migration.execute',
        'resolve' => 'migration.resolve',
        'rollback' => 'migration.rollback',
        'purge' => 'migration.purge',
    ],

    'canonical' => [
        // Explicit values override marker-based detection. Host/vertical extensions
        // may publish these flags or add their own definitions to the registry.
        'modules' => [],
        'module_markers' => [
            'vertical.field_service' => ['app/extensions/WorkCore', 'app/Domains/WorkCore'],
            'vertical.accommodation' => ['app/extensions/BookingEngine', 'app/extensions/Booking'],
            'vertical.real_estate' => ['app/extensions/RealEstate'],
            'vertical.salon' => ['app/extensions/Salon'],
            'vertical.fitness' => ['app/extensions/Fitness'],
            'vertical.automotive' => ['app/extensions/Automotive'],
            'vertical.ecommerce' => ['app/extensions/Ecommerce', 'app/extensions/Commerce'],
            'vertical.hire' => ['app/extensions/Hire', 'app/extensions/Rental'],
            'vertical.booking' => ['app/extensions/BookingEngine', 'app/extensions/Booking'],
        ],
        'minimum_fuzzy_confidence' => 0.85,
        'maximum_auto_merge_risk' => 0.40,
    ],
];
