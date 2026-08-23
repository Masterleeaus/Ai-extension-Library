<?php

declare(strict_types=1);

return [
    'name' => 'Fitness & Membership',
    'slug' => 'fitness',
    'icon' => '💪',
    'color' => '#FF9F43',
    'sub_verticals' => [
        'gyms',
        'yoga_studios',
        'pilates_studios',
        'personal_training',
        'cross_fit_gyms',
        'boxing_gyms',
        'martial_arts',
        'dance_studios',
        'cycling_studios',
        'swimming_pools',
        'tennis_courts',
        'golf_clubs',
        'rock_climbing',
        'fitness_bootcamps',
        'CrossFit_boxes',
        'sports_facilities',
        'wellness_centers',
        'rehabilitation_centers',
        'fitness_franchises',
        'luxury_gyms',
        'boutique_fitness',
    ],
    'features' => [
        'membership_management',
        'class_scheduling',
        'attendance_tracking',
        'member_retention',
        'billing_automation',
        'trainer_assignment',
        'workout_programs',
        'progress_tracking',
    ],
    'customizations' => [
        'people' => 'FitnessPeopleCustomization',
        'schedule' => 'FitnessScheduleCustomization',
        'money' => 'FitnessMoneyCustomization',
        'customer' => 'FitnessCustomerCustomization',
    ],
];
