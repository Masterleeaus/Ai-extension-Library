<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TitanAI Hybrid Architecture Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the unified TitanAI foundation shared by:
    | - Chatbot
    | - AIAgent
    | - AIChatPro
    |
    */

    'extensions' => [
        'chatbot' => [
            'enabled' => env('TITANAI_CHATBOT_ENABLED', true),
            'auto_register_to_unified_registry' => env('TITANAI_CHATBOT_AUTO_REGISTER', true),
            'emit_discovery_events' => env('TITANAI_CHATBOT_EMIT_DISCOVERY_EVENTS', true),
            'listen_to_events' => env('TITANAI_CHATBOT_LISTEN_TO_EVENTS', true),
        ],
        'aiagent' => [
            'enabled' => env('TITANAI_AIAGENT_ENABLED', true),
            'auto_register_to_unified_registry' => env('TITANAI_AIAGENT_AUTO_REGISTER', true),
            'emit_discovery_events' => env('TITANAI_AIAGENT_EMIT_DISCOVERY_EVENTS', true),
            'listen_to_events' => env('TITANAI_AIAGENT_LISTEN_TO_EVENTS', true),
            'memory_bridge' => [
                'enabled' => env('TITANAI_AIAGENT_MEMORY_BRIDGE_ENABLED', true),
                'strict' => env('TITANAI_AIAGENT_MEMORY_BRIDGE_STRICT', false),
            ],
        ],
        'aichatpro' => [
            'enabled' => env('TITANAI_AICHATPRO_ENABLED', true),
            'auto_register_to_unified_registry' => env('TITANAI_AICHATPRO_AUTO_REGISTER', true),
            'emit_discovery_events' => env('TITANAI_AICHATPRO_EMIT_DISCOVERY_EVENTS', true),
            'listen_to_events' => env('TITANAI_AICHATPRO_LISTEN_TO_EVENTS', true),
        ],
    ],

    'memory' => [
        'default_ttl' => env('TITANAI_MEMORY_DEFAULT_TTL'), // null = forever
        'auto_cleanup' => env('TITANAI_MEMORY_AUTO_CLEANUP', true),
        'emit_events' => env('TITANAI_MEMORY_EMIT_EVENTS', true),
        'context_entry_limit' => env('TITANAI_MEMORY_CONTEXT_ENTRY_LIMIT', 50),
    ],

    'registry' => [
        'allow_overrides' => env('TITANAI_REGISTRY_ALLOW_OVERRIDES', false),
        'cache_enabled' => env('TITANAI_REGISTRY_CACHE_ENABLED', false),
    ],

    'events' => [
        'enabled' => env('TITANAI_EVENTS_ENABLED', true),
        'strict' => env('TITANAI_EVENTS_STRICT', false),
        'idempotency_cache_size' => env('TITANAI_EVENTS_IDEMPOTENCY_CACHE_SIZE', 1000),
    ],

    'orchestration' => [
        'rethrow_failures' => env('TITANAI_ORCHESTRATION_RETHROW_FAILURES', false),
    ],

    'diagnostics' => [
        'recent_limit' => env('TITANAI_DIAGNOSTICS_RECENT_LIMIT', 50),
    ],

    'features' => [
        'cross_extension_actions' => env('TITANAI_CROSS_EXTENSION_ACTIONS', true),
        'cross_extension_connectors' => env('TITANAI_CROSS_EXTENSION_CONNECTORS', true),
        'shared_memory' => env('TITANAI_SHARED_MEMORY', true),
        'event_publishing' => env('TITANAI_EVENT_PUBLISHING', true),
    ],
];
