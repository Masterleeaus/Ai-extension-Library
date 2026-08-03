<?php

declare(strict_types=1);

return [
    'enabled' => false,
    'default_provider' => 'google-places',
    'providers' => [
        'google-places' => [
            'credential_reference' => null,
            'base_uri' => 'https://places.googleapis.com',
            'timeout_seconds' => 10,
            'retry_attempts' => 2,
            'field_masks' => [
                'search' => 'places.id,places.displayName,places.formattedAddress,places.location,places.primaryType,places.types,places.businessStatus,places.googleMapsUri,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.regularOpeningHours,places.currentOpeningHours,places.rating,places.userRatingCount',
                'details' => 'id,displayName,formattedAddress,location,primaryType,types,businessStatus,googleMapsUri,nationalPhoneNumber,internationalPhoneNumber,websiteUri,regularOpeningHours,currentOpeningHours,rating,userRatingCount',
            ],
        ],
    ],
    'limits' => [
        'maximum_results' => 100,
        'maximum_radius_metres' => 50000,
        'maximum_provider_page_size' => 20,
    ],
    'matching' => [
        'confirmed_threshold' => 0.86,
        'ambiguous_threshold' => 0.62,
    ],
    'ranking' => [
        'weights' => [
            'distance' => 0.30,
            'category' => 0.20,
            'rating' => 0.15,
            'review_count' => 0.10,
            'open_now' => 0.10,
            'contactability' => 0.10,
            'freshness' => 0.05,
        ],
    ],
];
