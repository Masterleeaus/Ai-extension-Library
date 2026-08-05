<?php

declare(strict_types=1);

/**
 * Feature Flags Configuration
 *
 * Centralized feature flag configuration for enabling/disabling features
 * without code changes or redeployment.
 */

return [
    // Knowledge Engine Feature
    'knowledge-engine' => [
        'enabled' => (bool) env('FEATURE_KNOWLEDGE_ENGINE', true),
        'description' => 'AI-powered document storage and retrieval',
        'versions' => ['1.0', '2.0'],
        'current_version' => '2.0',
    ],

    // Voice Engine Feature
    'voice-engine' => [
        'enabled' => (bool) env('FEATURE_VOICE_ENGINE', false),
        'description' => 'Text-to-speech and speech-to-text capabilities',
        'versions' => ['1.0'],
        'current_version' => '1.0',
    ],

    // Booking Engine Feature
    'booking-engine' => [
        'enabled' => (bool) env('FEATURE_BOOKING_ENGINE', true),
        'description' => 'Appointment and reservation management',
        'versions' => ['1.0', '2.0'],
        'current_version' => '2.0',
    ],

    // Commerce/E-commerce Feature
    'commerce' => [
        'enabled' => (bool) env('FEATURE_COMMERCE', true),
        'description' => 'Product catalog, cart, and checkout',
        'versions' => ['1.0'],
        'current_version' => '1.0',
        'capabilities' => [
            'products' => true,
            'cart' => true,
            'checkout' => true,
            'payments' => true,
            'shipping' => true,
        ],
    ],

    // WorkCore ERP Feature
    'workcore' => [
        'enabled' => (bool) env('FEATURE_WORKCORE', true),
        'description' => 'Enterprise Resource Planning system',
        'versions' => ['1.0', '2.0'],
        'current_version' => '2.0',
        'modules' => [
            'crm' => true,
            'accounting' => true,
            'inventory' => true,
            'workforce' => true,
            'scheduling' => true,
        ],
    ],

    // Chatbot Feature
    'chatbot' => [
        'enabled' => true,
        'description' => 'AI-powered conversational interface',
        'versions' => ['1.0', '2.0', '3.0'],
        'current_version' => '3.0',
        'capabilities' => [
            'text_chat' => true,
            'file_upload' => true,
            'image_generation' => false,
            'real_time_typing' => true,
        ],
    ],

    // AI Agent Feature
    'ai-agent' => [
        'enabled' => true,
        'description' => 'Autonomous AI agents for task automation',
        'versions' => ['1.0', '2.0'],
        'current_version' => '2.0',
    ],

    // Phone Call Agent Feature
    'phone-call-agent' => [
        'enabled' => true,
        'description' => 'AI-powered outbound phone call agent',
        'versions' => ['1.0'],
        'current_version' => '1.0',
    ],

    // Marketing Bot Feature
    'marketing-bot' => [
        'enabled' => true,
        'description' => 'Automated marketing and messaging campaigns',
        'versions' => ['1.0'],
        'current_version' => '1.0',
        'channels' => [
            'whatsapp' => true,
            'telegram' => true,
            'email' => true,
            'sms' => true,
        ],
    ],

    // Advanced Features (Beta)
    'beta-features' => [
        'enabled' => false,
        'description' => 'Experimental features in beta',
        'features' => [
            'multi-language-support' => false,
            'custom-llm-integration' => false,
            'advanced-analytics' => false,
        ],
    ],
];
