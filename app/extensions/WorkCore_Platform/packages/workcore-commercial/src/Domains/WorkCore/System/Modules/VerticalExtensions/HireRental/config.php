<?php

declare(strict_types=1);

return [
    'name' => 'Hire & Rental',
    'slug' => 'hire-rental',
    'icon' => '🔑',
    'color' => '#3498DB',
    'sub_verticals' => [
        'equipment_hire',
        'vehicle_rental',
        'property_rental',
        'tool_rental',
        'party_equipment',
        'vehicle_hire',
        'costume_rental',
        'furniture_rental',
        'it_equipment_rental',
        'catering_equipment',
        'sports_equipment_rental',
        'photo_equipment_rental',
        'wedding_rental',
        'audiovisual_rental',
        'construction_equipment',
        'scaffolding_rental',
        'crane_rental',
        'generator_rental',
        'boat_rental',
        'jet_ski_rental',
        'bike_rental',
        'car_lease',
    ],
    'features' => [
        'rental_agreements',
        'damage_assessment',
        'late_fees',
        'insurance_options',
        'deposit_management',
        'condition_verification',
        'extension_requests',
    ],
    'customizations' => [
        'hire' => 'HireRentalCustomization',
        'forms' => 'HireRentalFormsCustomization',
        'money' => 'HireRentalMoneyCustomization',
        'reach' => 'HireRentalReachCustomization',
    ],
];
