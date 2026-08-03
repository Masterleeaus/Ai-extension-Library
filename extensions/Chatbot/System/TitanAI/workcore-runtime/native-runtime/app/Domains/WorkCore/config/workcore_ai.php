<?php

declare(strict_types=1);

use App\Domains\WorkCore\System\AI\Security\EnvironmentCredentialResolver;

return [
    'enabled' => env('WORKCORE_NATIVE_AI_ENABLED', true),
    'credential_resolver' => EnvironmentCredentialResolver::class,

    'access' => [
        'capability' => 'workcore.ai',
        'use_permission' => 'workcore.ai.use',
        'tool_permission' => 'workcore.ai.execute_tools',
    ],

    'fallback_profiles' => [
        [
            'enabled' => env('WORKCORE_AI_OPENAI_ENABLED', false),
            'provider' => 'openai',
            'name' => 'OpenAI-compatible provider',
            'base_url' => env('WORKCORE_AI_OPENAI_BASE_URL', 'https://api.openai.com'),
            'secret_reference' => 'env:OPENAI_API_KEY',
            'model' => env('WORKCORE_AI_OPENAI_MODEL', 'gpt-4.1-mini'),
            'purpose' => '*',
            'priority' => 100,
        ],
        [
            'enabled' => env('WORKCORE_AI_LOCAL_ENABLED', false),
            'provider' => 'local',
            'name' => 'Local OpenAI-compatible model',
            'base_url' => env('WORKCORE_AI_LOCAL_BASE_URL', 'http://127.0.0.1:11434'),
            'secret_reference' => null,
            'model' => env('WORKCORE_AI_LOCAL_MODEL', 'llama3.2'),
            'purpose' => '*',
            'priority' => 200,
        ],
    ],

    'rate_limits' => [
        'requests_per_minute' => (int) env('WORKCORE_AI_REQUESTS_PER_MINUTE', 60),
    ],

    'budgets' => [
        'max_output_tokens_per_request' => (int) env('WORKCORE_AI_MAX_OUTPUT_TOKENS', 4096),
        'daily_company_tokens' => (int) env('WORKCORE_AI_DAILY_COMPANY_TOKENS', 0),
    ],

    'circuit_breaker' => [
        'failure_threshold' => (int) env('WORKCORE_AI_BREAKER_FAILURES', 3),
        'open_seconds' => (int) env('WORKCORE_AI_BREAKER_OPEN_SECONDS', 60),
    ],

    'sensitive_keys' => [
        'api_key', 'apikey', 'authorization', 'password', 'secret', 'token',
        'access_token', 'refresh_token', 'credential', 'private_key',
        'client_secret', 'bank_account', 'card_number', 'cvv',
    ],

    // Business-domain tool registries are deliberately excluded from this runtime-only pack.
    // The host may append implementations of DomainToolRegistryContract after installing
    // the relevant WorkCore domain modules.
    'tool_registries' => [],
];
