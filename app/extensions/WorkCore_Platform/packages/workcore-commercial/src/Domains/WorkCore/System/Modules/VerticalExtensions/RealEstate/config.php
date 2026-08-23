<?php

declare(strict_types=1);

return [
    'name' => 'Real Estate & Property Management',
    'slug' => 'real-estate',
    'icon' => '🏠',
    'color' => '#A8E6CF',
    'sub_verticals' => [
        'residential_agencies',
        'commercial_agencies',
        'property_management',
        'valuers',
        'commercial_leasing',
        'retail_leasing',
        'property_sales',
        'property_rentals',
        'land_agents',
        'strata_management',
        'facilities_management',
        'industrial_leasing',
        'office_leasing',
        'luxury_properties',
        'holiday_rentals',
        'student_housing',
        'aged_care_facilities',
        'retirement_communities',
        'apartment_management',
        'townhouse_management',
    ],
    'features' => [
        'tenant_screening',
        'lease_generation',
        'maintenance_ticketing',
        'virtual_tours',
        'property_listings',
        'document_management',
        'rent_collection',
        'tenant_portal',
    ],
    'customizations' => [
        'webpilot' => 'RealEstateWebPilotCustomization',
        'forms' => 'RealEstateFormsCustomization',
        'document_generator' => 'RealEstateDocumentGeneratorCustomization',
        'support' => 'RealEstateSupportCustomization',
    ],
];
