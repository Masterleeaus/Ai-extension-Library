<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the application handles multi-tenancy across all tables
    |
    */

    'enabled' => true,

    'default_company_id' => env('DEFAULT_COMPANY_ID'),

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Tables & Fields
    |--------------------------------------------------------------------------
    |
    | All assets must implement company_id (required), user_id (recommended),
    | and team_id (optional) for proper multi-tenant isolation
    |
    */

    'tables_with_full_coverage' => [
        // Tables with company_id + user_id + team_id
    ],

    'tables_with_partial_coverage' => [
        'products' => ['company_id', 'user_id'],
        'team_members' => ['user_id', 'team_id'],
        'teams' => ['user_id'],
        'file_ownership_access' => ['user_id'],
        'identity_profiles' => ['user_id'],
        'voice_engine_profiles' => ['user_id'],
    ],

    'tables_updated_this_release' => [
        // 29 tables added multi-tenant support
        'booking_engine_bookings' => ['company_id', 'user_id', 'team_id'],
        'booking_engine_providers' => ['company_id', 'user_id', 'team_id'],
        'booking_migration_batches' => ['company_id', 'user_id', 'team_id'],
        'booking_migration_records' => ['company_id', 'user_id', 'team_id'],
        'channels' => ['company_id', 'user_id', 'team_id'],
        'channel_inventory' => ['company_id', 'user_id', 'team_id'],
        'channel_mappings' => ['company_id', 'user_id', 'team_id'],
        'channel_orders' => ['company_id', 'user_id', 'team_id'],
        'channel_pricings' => ['company_id', 'user_id', 'team_id'],
        'commerce_contract_orders' => ['company_id', 'user_id', 'team_id'],
        'comparison_section_items' => ['company_id', 'user_id', 'team_id'],
        'ecommerce_integration_catalogs' => ['company_id', 'user_id', 'team_id'],
        'ecommerce_integration_orders' => ['company_id', 'user_id', 'team_id'],
        'file_ownership_registry' => ['company_id', 'user_id', 'team_id'],
        'footer_items' => ['company_id', 'user_id', 'team_id'],
        'frontend_channel_settings' => ['company_id', 'user_id', 'team_id'],
        'gatewayproducts' => ['company_id', 'user_id', 'team_id'],
        'health_check_result_history_items' => ['company_id', 'user_id', 'team_id'],
        'knowledge_engine_documents' => ['company_id', 'user_id', 'team_id'],
        'media_quarantine_records' => ['company_id', 'user_id', 'team_id'],
        'oldgatewayproducts' => ['company_id', 'user_id', 'team_id'],
        'revenuecat_products' => ['company_id', 'user_id', 'team_id'],
        'social_media_accounts' => ['company_id', 'user_id', 'team_id'],
        'subscription_invoices' => ['company_id', 'user_id', 'team_id'],
        'subscription_items' => ['company_id', 'user_id', 'team_id'],
        'user_orders' => ['company_id', 'user_id', 'team_id'],
        'workcore_business_customers' => ['company_id', 'user_id', 'team_id'],
        'workcore_business_products' => ['company_id', 'user_id', 'team_id'],
        'workcore_workforce_employees' => ['company_id', 'user_id', 'team_id'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Scoping
    |--------------------------------------------------------------------------
    |
    | When enabled, all queries automatically scope to current user's company_id
    | Disable by calling withoutGlobalScope('company_id') on query builder
    |
    */

    'auto_scope_enabled' => true,

    'scoping' => [
        'strict_mode' => env('MULTITENANT_STRICT_MODE', false),
        'fail_on_missing_company_id' => false,
        'log_cross_tenant_access' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Indexes for Performance
    |--------------------------------------------------------------------------
    |
    | All multi-tenant tables include:
    | - Index: company_id
    | - Index: company_id, user_id
    | - Foreign key: company_id → companies.id (cascade delete)
    |
    */
];
