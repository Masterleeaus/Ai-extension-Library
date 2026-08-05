<?php

return [
    'default_expiry_days' => 30,
    'max_images' => 20,
    'destinations' => [
        'facebook-marketplace' => [
            'label' => 'Facebook Marketplace',
            'official_posting_url' => 'https://www.facebook.com/marketplace/create/item',
            'allowed_external_hosts' => [
                'facebook.com',
                'www.facebook.com',
                'm.facebook.com',
            ],
            'completion_states' => [
                'ready_for_manual_post',
                'opened_official_destination',
                'manually_published',
                'renewal_ready_for_manual_post',
            ],
        ],
        'gumtree' => [
            'label' => 'Gumtree Australia',
            'official_posting_url' => 'https://www.gumtree.com.au/p-post-ad.html',
            'allowed_external_hosts' => [
                'gumtree.com.au',
                'www.gumtree.com.au',
            ],
            'completion_states' => [
                'ready_for_manual_post',
                'opened_official_destination',
                'manually_published',
                'renewal_ready_for_manual_post',
            ],
            'posting_guidance' => [
                'Use the correct category and physical location for the product or service.',
                'Prepare a unique advertisement rather than reposting duplicate copy.',
                'Business service advertisements may require the Services for Hire category.',
                'Keep buyer conversations and payment activity within Gumtree where available.',
            ],
        ],
    ],
];
