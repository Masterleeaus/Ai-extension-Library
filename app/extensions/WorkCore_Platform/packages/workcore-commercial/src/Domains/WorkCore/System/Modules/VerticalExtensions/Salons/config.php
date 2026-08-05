<?php

declare(strict_types=1);

return [
    'name' => 'Salons & Personal Care',
    'slug' => 'salons',
    'icon' => '💇',
    'color' => '#FF69B4',
    'sub_verticals' => [
        'hair_salons',
        'nail_salons',
        'spas',
        'massage_therapy',
        'tattoo_studios',
        'piercing_studios',
        'waxing_salons',
        'barber_shops',
        'beauty_salons',
        'eyelash_extensions',
        'eyebrow_threading',
        'tanning_salons',
        'laser_hair_removal',
        'skincare_clinics',
        'aesthetic_treatment',
        'personal_training',
        'makeup_artists',
        'hairdressing_academies',
        'wellness_centers',
        'holistic_practitioners',
    ],
    'features' => [
        'skill_based_booking',
        'service_add_ons',
        'loyalty_programs',
        'product_retail',
        'stylist_specialties',
        'client_profiles',
        'before_after_gallery',
    ],
    'customizations' => [
        'schedule' => 'SalonsScheduleCustomization',
        'omni' => 'SalonsOmniCustomization',
        'seller' => 'SalonsSellerCustomization',
        'customer' => 'SalonsCustomerCustomization',
    ],
];
