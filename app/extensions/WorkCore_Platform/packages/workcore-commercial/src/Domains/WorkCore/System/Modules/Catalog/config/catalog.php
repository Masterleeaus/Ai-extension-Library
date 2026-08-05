<?php

declare(strict_types=1);

return [
    'routes_enabled' => env('WORKCORE_CATALOG_ROUTES_ENABLED', false),
    'route_prefix' => env('WORKCORE_CATALOG_ROUTE_PREFIX', 'workcore/catalog'),
    'middleware' => [
        'api',
        'tenant.context',
    ],
    'permissions' => [
        'view' => 'workcore.catalog.view',
        'create_product' => 'workcore.catalog.product.create',
        'update_product' => 'workcore.catalog.product.update',
        'delete_product' => 'workcore.catalog.product.delete',
        'create_variant' => 'workcore.catalog.variant.create',
        'create_category' => 'workcore.catalog.category.create',
        'create_package' => 'workcore.catalog.service_package.create',
        'upload_media' => 'workcore.catalog.media.upload',
        'publish_product' => 'workcore.catalog.product.publish',
    ],
];
