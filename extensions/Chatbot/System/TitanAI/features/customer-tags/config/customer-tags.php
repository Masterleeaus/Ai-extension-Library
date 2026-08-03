<?php

declare(strict_types=1);

return [
    'system_chat' => 'system-ai-chat',
    'scopes' => ['customer', 'lead', 'property', 'job', 'invoice', 'conversation'],
    'features' => [
        'manual_tags', 'automatic_tags', 'smart_segments', 'tag_rules',
        'service_preferences', 'access_notes', 'pets_and_hazards',
        'vip_and_priority', 'payment_risk', 'late_payment', 'review_status',
        'recurring_customer', 'do_not_contact', 'consent_tracking',
        'tag_history', 'bulk_tagging', 'tag_based_automation', 'saved_filters',
    ],
    'authority' => 'WorkCore API',
];
