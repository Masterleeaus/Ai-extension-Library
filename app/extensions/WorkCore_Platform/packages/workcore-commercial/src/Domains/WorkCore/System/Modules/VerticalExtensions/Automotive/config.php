<?php

declare(strict_types=1);

return [
    'name' => 'Automotive Services',
    'slug' => 'automotive',
    'icon' => '🚗',
    'color' => '#2C3E50',
    'sub_verticals' => [
        'auto_repair',
        'mechanics',
        'tire_shops',
        'oil_change',
        'body_shops',
        'transmission_repair',
        'collision_repair',
        'detailing',
        'car_wash',
        'battery_service',
        'brake_service',
        'suspension_repair',
        'electrical_repair',
        'engine_repair',
        'tune_ups',
        'maintenance_services',
        'parts_suppliers',
        'customization_shops',
        'diagnostic_centers',
        'rust_protection',
        'windshield_repair',
        'airbag_services',
    ],
    'features' => [
        'service_packages',
        'parts_inventory',
        'technician_assignment',
        'warranty_tracking',
        'service_history',
        'maintenance_reminders',
        'vehicle_diagnostics',
    ],
    'customizations' => [
        'shop' => 'AutomotiveShopCustomization',
        'seller' => 'AutomotiveSellerCustomization',
        'work' => 'AutomotiveWorkCustomization',
        'customer' => 'AutomotiveCustomerCustomization',
    ],
];
