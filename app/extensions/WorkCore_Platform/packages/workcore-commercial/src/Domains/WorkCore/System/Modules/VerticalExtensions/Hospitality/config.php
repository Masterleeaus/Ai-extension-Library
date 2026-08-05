<?php

declare(strict_types=1);

return [
    'name' => 'Hospitality & Accommodation Services',
    'slug' => 'hospitality',
    'icon' => '🏨',
    'color' => '#4ECDC4',
    'sub_verticals' => [
        'hotels',
        'airbnb',
        'bnb',
        'holiday_parks',
        'resorts',
        'hostels',
        'vacation_rentals',
        'short_stay',
        'rooming_houses',
        'student_accommodation',
        'serviced_apartments',
        'guest_houses',
        'boutique_hotels',
        'luxury_accommodations',
        'backpackers',
        'glamping',
        'eco_lodges',
        'ski_lodges',
        'beach_resorts',
        'wellness_retreats',
    ],
    'features' => [
        'channel_synchronization',
        'dynamic_pricing',
        'housekeeping_management',
        'guest_communications',
        'availability_management',
        'inventory_tracking',
        'seasonal_pricing',
    ],
    'customizations' => [
        'schedule' => 'HospitalityScheduleCustomization',
        'money' => 'HospitalityMoneyCustomization',
        'omni' => 'HospitalityOmniCustomization',
        'work' => 'HospitalityWorkCustomization',
    ],
];
