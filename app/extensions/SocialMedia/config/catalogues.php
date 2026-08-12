<?php

return [
    'sources' => [
        'crm' => [
            'resolver' => env('TITAN_REACH_CRM_SOURCE_ADAPTER'),
            'record_types' => ['customer', 'lead', 'quote', 'enquiry'],
            'authoritative_fields' => ['identity', 'contact', 'lead_status', 'quote_value', 'enquiry_state'],
            'handoff_types' => ['lead', 'enquiry', 'quote_request'],
        ],
        'workcore' => [
            'resolver' => env('TITAN_REACH_WORKCORE_SOURCE_ADAPTER'),
            'record_types' => ['job', 'technician', 'service_area', 'maintenance_plan', 'facilities_maintenance'],
            'authoritative_fields' => ['job_status', 'service', 'service_area', 'technician', 'availability', 'maintenance_plan'],
            'handoff_types' => ['job', 'service_enquiry', 'emergency', 'maintenance'],
        ],
        'commerce' => [
            'resolver' => env('TITAN_REACH_COMMERCE_SOURCE_ADAPTER'),
            'record_types' => ['product', 'variant', 'inventory', 'price', 'order', 'collection'],
            'authoritative_fields' => ['sku', 'name', 'description', 'price', 'inventory', 'availability', 'collection'],
            'handoff_types' => ['product_enquiry', 'order_enquiry', 'return_enquiry'],
        ],
        'marketing' => [
            'resolver' => env('TITAN_REACH_MARKETING_SOURCE_ADAPTER'),
            'record_types' => ['campaign', 'audience', 'attribution'],
            'authoritative_fields' => ['campaign', 'audience', 'channel', 'attribution', 'consent'],
            'handoff_types' => ['campaign', 'attribution'],
        ],
        'bookings' => [
            'resolver' => env('TITAN_REACH_BOOKINGS_SOURCE_ADAPTER'),
            'record_types' => ['appointment', 'reservation', 'class', 'event', 'waitlist', 'capacity'],
            'authoritative_fields' => ['availability', 'capacity', 'schedule', 'resource', 'booking_url', 'waitlist'],
            'handoff_types' => ['booking', 'availability_enquiry', 'waitlist'],
        ],
        'property' => [
            'resolver' => env('TITAN_REACH_PROPERTY_SOURCE_ADAPTER'),
            'record_types' => ['property', 'room', 'stay', 'rate', 'listing', 'inspection', 'appraisal', 'lease'],
            'authoritative_fields' => ['property', 'address', 'room', 'rate', 'availability', 'listing_status', 'inspection', 'lease'],
            'handoff_types' => ['property_enquiry', 'inspection', 'appraisal', 'maintenance'],
        ],
        'automotive' => [
            'resolver' => env('TITAN_REACH_AUTOMOTIVE_SOURCE_ADAPTER'),
            'record_types' => ['vehicle', 'workshop_booking', 'inspection', 'part', 'fleet', 'vehicle_listing'],
            'authoritative_fields' => ['vehicle', 'vin', 'registration', 'booking', 'inspection', 'part', 'price', 'availability'],
            'handoff_types' => ['vehicle_enquiry', 'service_booking', 'inspection', 'roadside'],
        ],
        'hire' => [
            'resolver' => env('TITAN_REACH_HIRE_SOURCE_ADAPTER'),
            'record_types' => ['asset', 'rate', 'availability', 'bond', 'booking', 'delivery_collection', 'damage_return'],
            'authoritative_fields' => ['asset', 'rate', 'availability', 'bond', 'booking_window', 'delivery_collection', 'damage_return'],
            'handoff_types' => ['hire_enquiry', 'booking', 'delivery_collection', 'damage_return'],
        ],
    ],

    'destination_fields' => [
        'facebook' => ['content', 'media', 'url'],
        'instagram' => ['content', 'media', 'url'],
        'x' => ['content', 'url'],
        'linkedin' => ['title', 'content', 'media', 'url'],
        'tiktok' => ['content', 'media', 'url'],
        'youtube' => ['title', 'content', 'video', 'url'],
        'youtube-shorts' => ['title', 'content', 'video', 'url'],
        'ebay' => ['title', 'description', 'price', 'images', 'sku', 'availability'],
        'meta-ads' => ['title', 'content', 'media', 'url', 'objective'],
        'facebook-marketplace' => ['title', 'description', 'price', 'images', 'location', 'availability'],
        'gumtree' => ['title', 'description', 'price', 'images', 'location', 'availability'],
        'google-business-profile' => ['title', 'content', 'media', 'location_name', 'url', 'availability'],
        'pinterest' => ['title', 'description', 'image_url', 'url', 'price'],
        'export' => ['title', 'content', 'description', 'price', 'availability', 'location', 'media', 'images', 'video', 'url'],
    ],

    'verticals' => [
        'generic-business' => [
            'records' => [
                'crm:lead' => ['content_types' => ['business_update'], 'required' => ['name'], 'fields' => ['title' => 'name', 'content' => 'summary', 'location' => 'location']],
                'commerce:product' => ['content_types' => ['product_offer', 'marketplace_listing'], 'required' => ['name', 'price'], 'fields' => ['title' => 'name', 'description' => 'description', 'content' => 'description', 'price' => 'price', 'availability' => 'availability', 'images' => 'images', 'media' => 'images', 'url' => 'url', 'sku' => 'sku']],
                'bookings:event' => ['content_types' => ['event', 'booking_offer'], 'required' => ['name', 'schedule'], 'fields' => ['title' => 'name', 'content' => 'description', 'availability' => 'availability', 'url' => 'booking_url', 'location' => 'location']],
                'marketing:campaign' => ['content_types' => ['paid_creative', 'business_update'], 'required' => ['name'], 'fields' => ['title' => 'name', 'content' => 'message', 'objective' => 'objective', 'url' => 'url']],
            ],
            'handoff_targets' => ['lead' => ['crm'], 'enquiry' => ['crm'], 'booking' => ['bookings', 'crm']],
            'subtype_overrides' => [],
        ],

        'field-home-services' => [
            'records' => [
                'workcore:job' => ['content_types' => ['service_promotion', 'business_update'], 'required' => ['service', 'service_area'], 'fields' => ['title' => 'service', 'content' => 'description', 'location' => 'service_area', 'availability' => 'availability', 'media' => 'media', 'images' => 'media', 'url' => 'booking_url']],
                'workcore:maintenance_plan' => ['content_types' => ['service_promotion'], 'required' => ['name', 'service_area'], 'fields' => ['title' => 'name', 'content' => 'description', 'location' => 'service_area', 'price' => 'price', 'url' => 'booking_url']],
                'crm:quote' => ['content_types' => ['business_update'], 'required' => ['service'], 'fields' => ['title' => 'service', 'content' => 'summary', 'price' => 'quote_value', 'location' => 'service_area']],
                'commerce:product' => ['content_types' => ['product_offer', 'marketplace_listing'], 'required' => ['name', 'price'], 'fields' => ['title' => 'name', 'description' => 'description', 'price' => 'price', 'availability' => 'availability', 'images' => 'images', 'url' => 'url']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'workcore'], 'enquiry' => ['crm', 'workcore'], 'booking' => ['bookings', 'workcore', 'crm'], 'emergency' => ['workcore', 'crm']],
            'subtype_overrides' => [
                'facilities-maintenance' => ['preferred_sources' => ['workcore', 'property', 'crm'], 'destination_fields' => ['google-business-profile' => ['title', 'content', 'location_name', 'url']]],
            ],
        ],

        'accommodation' => [
            'records' => [
                'property:room' => ['content_types' => ['room_stay_offer', 'property_listing'], 'required' => ['name', 'property', 'rate'], 'fields' => ['title' => 'name', 'description' => 'description', 'price' => 'rate', 'availability' => 'availability', 'location' => 'location', 'images' => 'images', 'media' => 'images', 'url' => 'booking_url']],
                'property:stay' => ['content_types' => ['room_stay_offer', 'booking_offer'], 'required' => ['property', 'availability'], 'fields' => ['title' => 'property', 'content' => 'description', 'price' => 'rate', 'availability' => 'availability', 'url' => 'booking_url']],
                'bookings:reservation' => ['content_types' => ['booking_offer'], 'required' => ['availability'], 'fields' => ['title' => 'resource_name', 'content' => 'description', 'availability' => 'availability', 'url' => 'booking_url']],
            ],
            'handoff_targets' => ['lead' => ['bookings', 'crm'], 'enquiry' => ['bookings', 'crm'], 'booking' => ['bookings'], 'maintenance' => ['workcore', 'property']],
            'subtype_overrides' => [
                'rooming-house' => ['preferred_sources' => ['property', 'bookings', 'crm'], 'destination_fields' => ['gumtree' => ['title', 'description', 'price', 'images', 'location', 'availability']]],
            ],
        ],

        'real-estate' => [
            'records' => [
                'property:listing' => ['content_types' => ['property_listing'], 'required' => ['title', 'address'], 'fields' => ['title' => 'title', 'description' => 'description', 'price' => 'price', 'location' => 'address', 'images' => 'images', 'media' => 'images', 'url' => 'listing_url', 'availability' => 'inspection_times']],
                'property:inspection' => ['content_types' => ['event', 'property_listing'], 'required' => ['property', 'schedule'], 'fields' => ['title' => 'property', 'content' => 'description', 'location' => 'address', 'availability' => 'schedule', 'url' => 'booking_url']],
                'property:appraisal' => ['content_types' => ['service_promotion'], 'required' => ['property'], 'fields' => ['title' => 'property', 'content' => 'summary', 'location' => 'address', 'url' => 'enquiry_url']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'property'], 'enquiry' => ['crm', 'property'], 'inspection' => ['property', 'bookings'], 'maintenance' => ['property', 'workcore']],
            'subtype_overrides' => [
                'property-management' => ['preferred_sources' => ['property', 'workcore', 'crm'], 'destination_fields' => ['linkedin' => ['title', 'content', 'media', 'url']]],
            ],
        ],

        'salons-personal-care' => [
            'records' => [
                'bookings:appointment' => ['content_types' => ['booking_offer', 'service_promotion'], 'required' => ['service', 'availability'], 'fields' => ['title' => 'service', 'content' => 'description', 'price' => 'price', 'availability' => 'availability', 'url' => 'booking_url', 'media' => 'media']],
                'commerce:product' => ['content_types' => ['product_offer'], 'required' => ['name', 'price'], 'fields' => ['title' => 'name', 'description' => 'description', 'price' => 'price', 'availability' => 'availability', 'images' => 'images', 'url' => 'url']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'bookings'], 'enquiry' => ['crm', 'bookings'], 'booking' => ['bookings']],
            'subtype_overrides' => [
                'hair-salon' => ['preferred_sources' => ['bookings', 'crm', 'commerce'], 'destination_fields' => ['instagram' => ['content', 'media', 'url']]],
            ],
        ],

        'fitness-membership' => [
            'records' => [
                'bookings:class' => ['content_types' => ['class_session_offer', 'event', 'booking_offer'], 'required' => ['name', 'schedule'], 'fields' => ['title' => 'name', 'content' => 'description', 'availability' => 'capacity', 'location' => 'location', 'url' => 'booking_url', 'media' => 'media']],
                'bookings:capacity' => ['content_types' => ['membership_offer', 'booking_offer'], 'required' => ['resource', 'availability'], 'fields' => ['title' => 'resource', 'content' => 'description', 'availability' => 'availability', 'url' => 'booking_url']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'bookings'], 'enquiry' => ['crm', 'bookings'], 'booking' => ['bookings'], 'membership' => ['bookings', 'crm']],
            'subtype_overrides' => [
                'gym-fitness-centre' => ['preferred_sources' => ['bookings', 'crm', 'commerce'], 'destination_fields' => ['facebook' => ['content', 'media', 'url']]],
            ],
        ],

        'automotive-services' => [
            'records' => [
                'automotive:vehicle_listing' => ['content_types' => ['vehicle_listing', 'marketplace_listing'], 'required' => ['title', 'price'], 'fields' => ['title' => 'title', 'description' => 'description', 'price' => 'price', 'availability' => 'availability', 'location' => 'location', 'images' => 'images', 'url' => 'listing_url']],
                'automotive:workshop_booking' => ['content_types' => ['booking_offer', 'service_promotion'], 'required' => ['service', 'availability'], 'fields' => ['title' => 'service', 'content' => 'description', 'price' => 'price', 'availability' => 'availability', 'url' => 'booking_url']],
                'automotive:part' => ['content_types' => ['product_offer', 'marketplace_listing'], 'required' => ['name', 'price'], 'fields' => ['title' => 'name', 'description' => 'description', 'price' => 'price', 'availability' => 'availability', 'images' => 'images', 'url' => 'url']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'automotive'], 'enquiry' => ['crm', 'automotive'], 'booking' => ['automotive', 'bookings'], 'roadside' => ['automotive', 'crm']],
            'subtype_overrides' => [
                'mechanical-workshop' => ['preferred_sources' => ['automotive', 'crm', 'bookings'], 'destination_fields' => ['google-business-profile' => ['title', 'content', 'location_name', 'url', 'availability']]],
            ],
        ],

        'ecommerce-retail' => [
            'records' => [
                'commerce:product' => ['content_types' => ['product_offer', 'marketplace_listing'], 'required' => ['name', 'price'], 'fields' => ['title' => 'name', 'description' => 'description', 'content' => 'description', 'price' => 'price', 'availability' => 'availability', 'images' => 'images', 'media' => 'images', 'url' => 'url', 'sku' => 'sku']],
                'commerce:collection' => ['content_types' => ['business_update', 'product_offer'], 'required' => ['name'], 'fields' => ['title' => 'name', 'content' => 'description', 'images' => 'images', 'url' => 'url']],
                'commerce:inventory' => ['content_types' => ['product_offer'], 'required' => ['sku', 'availability'], 'fields' => ['title' => 'name', 'availability' => 'availability', 'price' => 'price', 'sku' => 'sku']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'commerce'], 'enquiry' => ['commerce', 'crm'], 'order' => ['commerce'], 'return' => ['commerce', 'crm']],
            'subtype_overrides' => [
                'online-store' => ['preferred_sources' => ['commerce', 'crm', 'marketing'], 'destination_fields' => ['ebay' => ['title', 'description', 'price', 'images', 'sku', 'availability']]],
            ],
        ],

        'hire-rental' => [
            'records' => [
                'hire:asset' => ['content_types' => ['hire_rental_listing', 'marketplace_listing'], 'required' => ['name', 'rate'], 'fields' => ['title' => 'name', 'description' => 'description', 'price' => 'rate', 'availability' => 'availability', 'location' => 'location', 'images' => 'images', 'url' => 'booking_url']],
                'hire:availability' => ['content_types' => ['hire_rental_listing', 'booking_offer'], 'required' => ['asset', 'availability'], 'fields' => ['title' => 'asset', 'content' => 'description', 'price' => 'rate', 'availability' => 'availability', 'url' => 'booking_url']],
                'hire:bond' => ['content_types' => ['hire_rental_listing'], 'required' => ['asset'], 'fields' => ['title' => 'asset', 'content' => 'terms', 'price' => 'rate', 'availability' => 'availability']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'hire'], 'enquiry' => ['hire', 'crm'], 'booking' => ['hire', 'bookings'], 'damage_return' => ['hire']],
            'subtype_overrides' => [
                'equipment-hire' => ['preferred_sources' => ['hire', 'crm', 'bookings'], 'destination_fields' => ['gumtree' => ['title', 'description', 'price', 'images', 'location', 'availability']]],
            ],
        ],

        'booking-capacity' => [
            'records' => [
                'bookings:appointment' => ['content_types' => ['booking_offer'], 'required' => ['resource', 'availability'], 'fields' => ['title' => 'resource', 'content' => 'description', 'price' => 'price', 'availability' => 'availability', 'location' => 'location', 'url' => 'booking_url']],
                'bookings:event' => ['content_types' => ['event', 'booking_offer'], 'required' => ['name', 'schedule'], 'fields' => ['title' => 'name', 'content' => 'description', 'price' => 'price', 'availability' => 'capacity', 'location' => 'location', 'url' => 'booking_url', 'media' => 'media']],
                'bookings:waitlist' => ['content_types' => ['booking_offer'], 'required' => ['resource'], 'fields' => ['title' => 'resource', 'content' => 'description', 'availability' => 'waitlist_status', 'url' => 'booking_url']],
            ],
            'handoff_targets' => ['lead' => ['crm', 'bookings'], 'enquiry' => ['bookings', 'crm'], 'booking' => ['bookings'], 'waitlist' => ['bookings']],
            'subtype_overrides' => [
                'appointment-based-service' => ['preferred_sources' => ['bookings', 'crm'], 'destination_fields' => ['google-business-profile' => ['title', 'content', 'location_name', 'url', 'availability']]],
            ],
        ],
    ],
];
