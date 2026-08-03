<?php

return [
    'default_provider' => env('TITAN_AI_PROVIDER', 'openai'),
    'default_model' => env('TITAN_AI_MODEL', 'gpt-4o-mini'),
    'default_system_prompt' => 'You are Titan Zero, a privacy-aware business operating assistant. Use the same assistant identity, tools, governance and knowledge across every authorised interface.',
    'memory_message_limit' => 30,
    'temperature' => 0.7,
    'openai_compatible' => [
        'endpoint' => env('TITAN_AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'key' => env('TITAN_AI_KEY', env('OPENAI_API_KEY')),
        'timeout' => 120,
    ],
    'compatible_completion_services' => [
        'App\\Services\\AI\\UnifiedChatCompletionService',
        'App\\Services\\AI\\ChatCompletionService',
    ],
    'default_capabilities' => ['chat','skills','files','web','canvas','research','memory','knowledge','generative-ui'],
    'channel_capabilities' => [
        'aichatpro' => ['chat','skills','files','web','canvas','research','memory','knowledge','generative-ui'],
        'chatbot' => ['chat','skills','files','memory','knowledge','generative-ui'],
        'web' => ['chat','files','memory','knowledge','generative-ui'],
    ],
    'capabilities' => [
        'chat' => ['label' => 'Chat'],
        'skills' => ['label' => 'Skills'],
        'files' => ['label' => 'File chat'],
        'web' => ['label' => 'Web research'],
        'canvas' => ['label' => 'Canvas'],
        'research' => ['label' => 'Deep research'],
        'memory' => ['label' => 'Memory'],
        'knowledge' => ['label' => 'Knowledge'],
        'generative-ui' => ['label' => 'Generative UI'],
        'image' => ['label' => 'Smart image'],
        'notion' => ['label' => 'Notion'],
    ],
];
