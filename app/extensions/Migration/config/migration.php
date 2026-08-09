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
];
