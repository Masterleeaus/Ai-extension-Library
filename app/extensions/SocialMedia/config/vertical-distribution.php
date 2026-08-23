<?php

return [
    'profile_version' => '2026.08.05.1',
    'vertical_context_resolver' => env('TITAN_REACH_VERTICAL_CONTEXT_RESOLVER'),

    'content_types' => [
        'social_post',
        'marketplace_listing',
        'classified_listing',
        'product_offer',
        'service_promotion',
        'business_update',
        'paid_creative',
        'event',
        'property_listing',
        'vehicle_listing',
        'job_listing',
        'room_stay_offer',
        'membership_offer',
        'class_session_offer',
        'booking_offer',
        'hire_rental_listing',
    ],

    'generic_profile' => [
        'slug' => 'generic-business',
        'label' => 'Generic Business',
        'subtypes' => [],
        'content_types' => [
            'social_post',
            'business_update',
            'service_promotion',
            'product_offer',
            'paid_creative',
            'event',
            'job_listing',
        ],
        'destination_suitability' => [
            'facebook' => 'supported',
            'instagram' => 'supported',
            'x' => 'supported',
            'linkedin' => 'supported',
            'tiktok' => 'special-case',
            'youtube' => 'special-case',
            'youtube-shorts' => 'special-case',
            'ebay' => 'special-case',
            'meta-ads' => 'supported',
            'facebook-marketplace' => 'special-case',
            'gumtree' => 'special-case',
            'google-business-profile' => 'supported',
            'pinterest' => 'special-case',
            'export' => 'primary',
        ],
        'required_fields' => ['title', 'content', 'call_to_action'],
        'media_guidance' => ['Use current brand assets and truthful business-specific imagery.'],
        'calls_to_action' => ['Learn more', 'Contact us', 'Request details'],
        'handoff_targets' => [
            'lead' => ['crm'],
            'enquiry' => ['crm'],
            'booking' => ['bookings', 'crm'],
        ],
        'compliance_warnings' => ['Do not invent prices, availability, licences, addresses or performance claims.'],
    ],

    'verticals' => [
        'field-home-services' => [
            'slug' => 'field-home-services',
            'label' => 'Field and Home Services',
            'subtypes' => [
                'residential-cleaning', 'commercial-cleaning', 'carpet-cleaning', 'upholstery-cleaning',
                'window-cleaning', 'plumbing', 'electrical', 'carpentry-joinery', 'painting-decorating',
                'building-renovation', 'handyman-property-maintenance', 'landscaping-gardening',
                'lawn-mowing', 'arborist-tree-removal', 'pest-control', 'locksmith', 'roofing-guttering',
                'hvac-heating-cooling', 'appliance-repair', 'solar-installation-maintenance',
                'security-system-installation', 'pool-spa-maintenance', 'waste-rubbish-removal',
                'pressure-washing', 'mobile-technician', 'inspection-services',
                'ndis-home-maintenance-support', 'facilities-maintenance',
            ],
            'content_types' => [
                'social_post', 'service_promotion', 'business_update', 'paid_creative', 'event',
                'product_offer', 'classified_listing', 'job_listing', 'booking_offer',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'supported', 'tiktok' => 'supported', 'youtube' => 'supported',
                'youtube-shorts' => 'supported', 'ebay' => 'special-case', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'special-case', 'gumtree' => 'supported',
                'google-business-profile' => 'primary', 'pinterest' => 'supported', 'export' => 'primary',
            ],
            'required_fields' => ['service', 'service_area', 'availability', 'call_to_action'],
            'media_guidance' => [
                'Prefer genuine before-and-after, technician, equipment and completed-job media.',
                'Obtain customer and property consent before publishing identifiable images.',
            ],
            'calls_to_action' => ['Request a quote', 'Book a service', 'Call for urgent help', 'Ask about a maintenance plan'],
            'handoff_targets' => [
                'lead' => ['crm', 'workcore'],
                'enquiry' => ['crm', 'workcore'],
                'booking' => ['bookings', 'workcore', 'crm'],
                'emergency' => ['workcore', 'crm'],
            ],
            'compliance_warnings' => [
                'Do not claim trade licences, insurance, NDIS registration or emergency response times unless verified.',
            ],
        ],

        'accommodation' => [
            'slug' => 'accommodation',
            'label' => 'BnB, Hotel and Rooming Services',
            'subtypes' => [
                'hotel', 'motel', 'resort', 'bed-breakfast', 'airbnb-short-stay', 'holiday-rental-management',
                'serviced-apartments', 'hostel', 'guesthouse', 'boutique-accommodation', 'rooming-house',
                'boarding-house', 'student-accommodation', 'worker-accommodation', 'caravan-park',
                'holiday-park', 'farm-stay', 'retreat-centre', 'co-living', 'property-turnover-cleaning',
                'linen-housekeeping', 'accommodation-maintenance',
            ],
            'content_types' => [
                'social_post', 'business_update', 'paid_creative', 'event', 'property_listing',
                'room_stay_offer', 'booking_offer', 'service_promotion', 'job_listing',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'supported', 'tiktok' => 'primary', 'youtube' => 'supported',
                'youtube-shorts' => 'primary', 'ebay' => 'not-applicable', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'special-case', 'gumtree' => 'special-case',
                'google-business-profile' => 'primary', 'pinterest' => 'primary', 'export' => 'primary',
            ],
            'required_fields' => ['property', 'room_or_stay', 'location', 'availability', 'booking_url'],
            'media_guidance' => [
                'Use accurate room, property, amenity and local-area imagery.',
                'Disclose material accessibility, occupancy and shared-facility details where relevant.',
            ],
            'calls_to_action' => ['Check availability', 'Book direct', 'View rooms', 'Explore the property'],
            'handoff_targets' => [
                'lead' => ['bookings', 'crm'],
                'enquiry' => ['bookings', 'crm'],
                'booking' => ['bookings'],
                'maintenance' => ['workcore', 'property'],
            ],
            'compliance_warnings' => [
                'Do not invent availability, nightly rates, occupancy limits, star ratings or included amenities.',
            ],
        ],

        'real-estate' => [
            'slug' => 'real-estate',
            'label' => 'Real Estate',
            'subtypes' => [
                'residential-agency', 'commercial-agency', 'property-management', 'owners-corporation-strata',
                'buyers-agent', 'sales-agent', 'leasing-agent', 'property-developer', 'building-property-inspection',
                'valuer', 'conveyancing', 'mortgage-finance-broker', 'real-estate-photography', 'property-staging',
                'auctioneer', 'tenant-placement', 'short-term-rental-management', 'facilities-asset-management',
                'maintenance-coordination', 'landlord-investor-portfolio-management',
            ],
            'content_types' => [
                'social_post', 'business_update', 'paid_creative', 'event', 'property_listing',
                'service_promotion', 'job_listing', 'booking_offer',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'primary', 'tiktok' => 'supported', 'youtube' => 'supported',
                'youtube-shorts' => 'supported', 'ebay' => 'not-applicable', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'special-case', 'gumtree' => 'special-case',
                'google-business-profile' => 'primary', 'pinterest' => 'primary', 'export' => 'primary',
            ],
            'required_fields' => ['property_reference', 'listing_type', 'location', 'price_or_terms', 'inspection_or_contact'],
            'media_guidance' => [
                'Use current authorised property media and clearly label artist impressions or staged imagery.',
                'Do not publish tenant-identifying information or unapproved occupied-property media.',
            ],
            'calls_to_action' => ['Book an inspection', 'Request an appraisal', 'Contact the agent', 'View property details'],
            'handoff_targets' => [
                'lead' => ['property', 'crm'],
                'enquiry' => ['property', 'crm'],
                'booking' => ['property', 'bookings', 'crm'],
                'maintenance' => ['property', 'workcore'],
            ],
            'compliance_warnings' => [
                'Do not invent property features, price guides, availability, inspection times or regulatory disclosures.',
            ],
        ],

        'salons-personal-care' => [
            'slug' => 'salons-personal-care',
            'label' => 'Salons and Personal Care',
            'subtypes' => [
                'hair-salon', 'barber', 'beauty-salon', 'nail-salon', 'day-spa', 'massage-therapy',
                'skin-facial-clinic', 'cosmetic-clinic', 'tattoo-studio', 'piercing-studio', 'makeup-artist',
                'eyelash-eyebrow', 'tanning-studio', 'waxing-hair-removal', 'mobile-hairdresser-beautician',
                'bridal-beauty', 'personal-stylist', 'wellness-practitioner', 'cosmetic-injector',
                'grooming-personal-care-studio',
            ],
            'content_types' => [
                'social_post', 'service_promotion', 'business_update', 'paid_creative', 'event',
                'product_offer', 'booking_offer', 'job_listing',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'special-case',
                'linkedin' => 'special-case', 'tiktok' => 'primary', 'youtube' => 'supported',
                'youtube-shorts' => 'primary', 'ebay' => 'special-case', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'special-case', 'gumtree' => 'special-case',
                'google-business-profile' => 'primary', 'pinterest' => 'primary', 'export' => 'primary',
            ],
            'required_fields' => ['service_or_product', 'price_or_offer', 'staff_or_location', 'booking_url'],
            'media_guidance' => [
                'Use consented portfolio, treatment-space, product and staff imagery.',
                'Before-and-after media requires explicit client consent and accurate treatment context.',
            ],
            'calls_to_action' => ['Book an appointment', 'View services', 'Claim this offer', 'Buy a gift card'],
            'handoff_targets' => [
                'lead' => ['bookings', 'crm'],
                'enquiry' => ['bookings', 'crm'],
                'booking' => ['bookings'],
                'product' => ['commerce', 'crm'],
            ],
            'compliance_warnings' => [
                'Do not make unsupported medical, cosmetic, healing or guaranteed-result claims.',
            ],
        ],

        'fitness-membership' => [
            'slug' => 'fitness-membership',
            'label' => 'Fitness and Membership Businesses',
            'subtypes' => [
                'gym', 'fitness-centre', 'personal-trainer', 'group-fitness-studio', 'yoga-studio',
                'pilates-studio', 'crossfit-functional-fitness', 'martial-arts-school', 'boxing-gym',
                'dance-school', 'swimming-school', 'sports-club', 'recreation-centre', 'wellness-club',
                'health-coaching', 'physiotherapy-exercise-program', 'outdoor-bootcamp',
                'online-fitness-membership', 'community-social-club', 'membership-association',
                'subscription-training-program', 'childrens-activity-sports-program',
            ],
            'content_types' => [
                'social_post', 'business_update', 'paid_creative', 'event', 'membership_offer',
                'class_session_offer', 'booking_offer', 'service_promotion', 'job_listing',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'supported', 'tiktok' => 'primary', 'youtube' => 'primary',
                'youtube-shorts' => 'primary', 'ebay' => 'special-case', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'special-case', 'gumtree' => 'special-case',
                'google-business-profile' => 'primary', 'pinterest' => 'supported', 'export' => 'primary',
            ],
            'required_fields' => ['programme_or_membership', 'schedule', 'location_or_delivery', 'eligibility', 'booking_or_join_url'],
            'media_guidance' => [
                'Use consented class, facility, coach and programme imagery.',
                'Children must not be identifiable without appropriate guardian consent.',
            ],
            'calls_to_action' => ['Join now', 'Book a trial', 'View the timetable', 'Reserve a class'],
            'handoff_targets' => [
                'lead' => ['memberships', 'crm'],
                'enquiry' => ['memberships', 'bookings', 'crm'],
                'booking' => ['bookings', 'memberships'],
                'membership' => ['memberships', 'crm'],
            ],
            'compliance_warnings' => [
                'Do not promise health outcomes, weight loss, performance gains or suitability without evidence.',
            ],
        ],

        'automotive-services' => [
            'slug' => 'automotive-services',
            'label' => 'Automotive Services',
            'subtypes' => [
                'mechanical-workshop', 'mobile-mechanic', 'auto-electrician', 'tyre-wheel', 'car-detailing',
                'mobile-car-wash', 'panel-beater', 'smash-repair', 'windscreen-repair-replacement',
                'vehicle-inspection', 'roadworthy-certificate', 'towing', 'roadside-assistance',
                'car-dealership', 'used-vehicle-dealership', 'motorcycle-repair', 'truck-fleet-maintenance',
                'heavy-machinery-repair', 'auto-parts-retail', 'car-audio-accessories',
                'paint-protection-vehicle-wrap', 'fleet-management', 'vehicle-air-conditioning',
            ],
            'content_types' => [
                'social_post', 'service_promotion', 'business_update', 'paid_creative', 'vehicle_listing',
                'product_offer', 'marketplace_listing', 'booking_offer', 'job_listing',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'supported', 'tiktok' => 'primary', 'youtube' => 'primary',
                'youtube-shorts' => 'primary', 'ebay' => 'primary', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'primary', 'gumtree' => 'primary',
                'google-business-profile' => 'primary', 'pinterest' => 'special-case', 'export' => 'primary',
            ],
            'required_fields' => ['service_vehicle_or_part', 'condition', 'price_or_quote', 'location', 'booking_or_enquiry'],
            'media_guidance' => [
                'Use accurate vehicle, part, workshop and completed-repair media.',
                'Remove number plates, customer details and identifying documents unless publication is authorised.',
            ],
            'calls_to_action' => ['Book a service', 'Request a quote', 'View vehicle details', 'Check availability'],
            'handoff_targets' => [
                'lead' => ['automotive', 'crm'],
                'enquiry' => ['automotive', 'crm'],
                'booking' => ['automotive', 'bookings', 'crm'],
                'vehicle' => ['automotive', 'commerce'],
            ],
            'compliance_warnings' => [
                'Do not invent roadworthy status, service history, odometer readings, warranty or vehicle condition.',
            ],
        ],

        'ecommerce-retail' => [
            'slug' => 'ecommerce-retail',
            'label' => 'E-commerce and Retail',
            'subtypes' => [
                'online-store', 'physical-retail', 'omnichannel-retail', 'fashion-store', 'homeware-furniture',
                'electronics-retail', 'beauty-cosmetics-retail', 'health-wellness-retail', 'pet-supplies',
                'food-specialty-grocery', 'florist-gift-shop', 'hardware-trade-supply', 'sporting-goods',
                'jewellery-accessories', 'subscription-box', 'wholesale-distributor', 'product-manufacturer',
                'marketplace-seller', 'dropshipping', 'print-on-demand', 'social-commerce',
                'click-collect', 'multi-location-retail', 'pop-up-market-vendor',
            ],
            'content_types' => [
                'social_post', 'product_offer', 'marketplace_listing', 'business_update', 'paid_creative',
                'event', 'job_listing', 'booking_offer',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'supported', 'tiktok' => 'primary', 'youtube' => 'supported',
                'youtube-shorts' => 'primary', 'ebay' => 'primary', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'primary', 'gumtree' => 'supported',
                'google-business-profile' => 'primary', 'pinterest' => 'primary', 'export' => 'primary',
            ],
            'required_fields' => ['product_reference', 'title', 'price', 'availability', 'destination_url'],
            'media_guidance' => [
                'Use accurate product, variant, packaging and lifestyle media.',
                'Label generated or illustrative media when it could affect purchase expectations.',
            ],
            'calls_to_action' => ['Shop now', 'View product', 'Check stock', 'Collect in store'],
            'handoff_targets' => [
                'lead' => ['commerce', 'crm'],
                'enquiry' => ['commerce', 'crm'],
                'order' => ['commerce'],
                'inventory' => ['commerce'],
            ],
            'compliance_warnings' => [
                'Do not invent price, stock, delivery times, product specifications, discounts or warranty terms.',
            ],
        ],

        'hire-rental' => [
            'slug' => 'hire-rental',
            'label' => 'Hire and Rental',
            'subtypes' => [
                'equipment-hire', 'tool-hire', 'vehicle-rental', 'car-van-hire', 'truck-trailer-hire',
                'machinery-plant-hire', 'party-event-equipment-hire', 'furniture-hire', 'marquee-staging-hire',
                'audio-visual-hire', 'costume-formalwear-hire', 'bicycle-scooter-rental',
                'boat-watercraft-hire', 'caravan-campervan-hire', 'storage-rental',
                'portable-building-container-hire', 'cleaning-equipment-rental',
                'medical-mobility-equipment-hire', 'baby-equipment-hire', 'camera-photography-hire',
                'sports-equipment-rental', 'workspace-room-hire',
            ],
            'content_types' => [
                'social_post', 'hire_rental_listing', 'marketplace_listing', 'product_offer',
                'business_update', 'paid_creative', 'booking_offer', 'job_listing',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'supported', 'tiktok' => 'supported', 'youtube' => 'supported',
                'youtube-shorts' => 'supported', 'ebay' => 'special-case', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'primary', 'gumtree' => 'primary',
                'google-business-profile' => 'primary', 'pinterest' => 'supported', 'export' => 'primary',
            ],
            'required_fields' => ['asset_reference', 'rate', 'availability', 'bond_or_terms', 'collection_or_delivery', 'booking_url'],
            'media_guidance' => [
                'Use current asset-condition, accessory, dimensions and use-case media.',
                'Clearly distinguish included equipment from optional extras.',
            ],
            'calls_to_action' => ['Check availability', 'Get a hire quote', 'Reserve this asset', 'View hire terms'],
            'handoff_targets' => [
                'lead' => ['hire', 'bookings', 'crm'],
                'enquiry' => ['hire', 'bookings', 'crm'],
                'booking' => ['hire', 'bookings'],
                'asset' => ['hire'],
            ],
            'compliance_warnings' => [
                'Do not invent availability, capacity, damage status, bond, delivery cost or licensing requirements.',
            ],
        ],

        'booking-capacity' => [
            'slug' => 'booking-capacity',
            'label' => 'Booking, Reservation and Capacity-Based Businesses',
            'subtypes' => [
                'restaurant-cafe', 'function-venue', 'wedding-venue', 'conference-meeting-space',
                'coworking-space', 'photography-studio', 'training-room', 'escape-room',
                'entertainment-venue', 'tour-activity-operator', 'travel-excursion', 'boat-charter',
                'bus-transport-booking', 'appointment-professional-service', 'medical-allied-health-clinic',
                'dental-practice', 'veterinary-clinic', 'counselling-therapy', 'tutor-education-provider',
                'childcare-activity-centre', 'class-workshop', 'event-organiser', 'ticketed-attraction',
                'campsite-caravan-site', 'parking-space', 'sports-court-facility',
                'shared-equipment-resource-booking',
            ],
            'content_types' => [
                'social_post', 'business_update', 'paid_creative', 'event', 'booking_offer',
                'class_session_offer', 'service_promotion', 'job_listing',
            ],
            'destination_suitability' => [
                'facebook' => 'primary', 'instagram' => 'primary', 'x' => 'supported',
                'linkedin' => 'supported', 'tiktok' => 'supported', 'youtube' => 'supported',
                'youtube-shorts' => 'supported', 'ebay' => 'not-applicable', 'meta-ads' => 'primary',
                'facebook-marketplace' => 'special-case', 'gumtree' => 'special-case',
                'google-business-profile' => 'primary', 'pinterest' => 'supported', 'export' => 'primary',
            ],
            'required_fields' => ['bookable_resource', 'availability', 'capacity', 'location_or_delivery', 'booking_url'],
            'media_guidance' => [
                'Use accurate venue, class, provider, resource and experience media.',
                'Do not imply capacity or availability that is not connected to the authoritative booking source.',
            ],
            'calls_to_action' => ['Book now', 'Reserve a place', 'View availability', 'Join the waitlist'],
            'handoff_targets' => [
                'lead' => ['bookings', 'crm'],
                'enquiry' => ['bookings', 'crm'],
                'booking' => ['bookings'],
                'capacity' => ['bookings'],
            ],
            'compliance_warnings' => [
                'Do not invent appointment times, seats, rooms, staff availability, clinical claims or ticket capacity.',
            ],
        ],
    ],
];
