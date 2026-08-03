<?php

return [
    'sources' => [
        'workcore_v2' => ['authority' => 'canonical', 'role' => 'operational domain and sole business write authority'],
        'extension_sdk_v2' => ['authority' => 'canonical', 'role' => 'extension tenancy, registration and dependency contract'],
        'magicai_host' => ['authority' => 'host', 'role' => 'authentication, companies, plans, providers and extension lifecycle'],
        'chatbot_pwa' => ['authority' => 'surface', 'role' => 'customer and field-worker local-first conversational interface'],
        'interaction_engine' => ['authority' => 'donor', 'role' => 'interface and engine contract catalogue; no direct boot'],
        'titan_bos_modules' => ['authority' => 'donor', 'role' => 'capability sources until explicitly promoted'],
        'ai_system_extensions' => ['authority' => 'donor', 'role' => 'AI capabilities and adapters subject to canonical ownership'],
        'base_system_extensions' => ['authority' => 'donor', 'role' => 'optional host capabilities subject to extension registry'],
    ],
    'legacy_embedded_workcore_enabled' => (bool) env('TITAN_LEGACY_EMBEDDED_WORKCORE', false),
];
