<?php

declare(strict_types=1);

/**
 * Environment Configuration
 *
 * This file documents all environment variables used in the application.
 * Each variable includes its type, default value, description, and whether it contains sensitive data.
 *
 * Usage: config('environment.VARIABLE_NAME')
 */

return [
    // Application Settings
    'APP_NAME' => [
        'type' => 'string',
        'default' => 'Ai-extensions',
        'description' => 'Application name used in UI and emails',
        'secret' => false,
    ],
    'APP_ENV' => [
        'type' => 'enum',
        'enum' => ['local', 'testing', 'staging', 'production'],
        'default' => 'local',
        'description' => 'Application environment',
        'secret' => false,
    ],
    'APP_DEBUG' => [
        'type' => 'boolean',
        'default' => false,
        'description' => 'Enable debug mode (NEVER enable in production)',
        'secret' => false,
    ],
    'APP_URL' => [
        'type' => 'url',
        'default' => 'http://localhost:8000',
        'description' => 'Application root URL',
        'secret' => false,
    ],

    // Database Configuration
    'DB_CONNECTION' => [
        'type' => 'enum',
        'enum' => ['mysql', 'postgresql', 'sqlite'],
        'default' => 'mysql',
        'description' => 'Database connection type',
        'secret' => false,
    ],
    'DB_HOST' => [
        'type' => 'string',
        'default' => '127.0.0.1',
        'description' => 'Database host address',
        'secret' => false,
    ],
    'DB_PORT' => [
        'type' => 'integer',
        'default' => 3306,
        'description' => 'Database port',
        'secret' => false,
    ],
    'DB_DATABASE' => [
        'type' => 'string',
        'default' => 'ai_extensions',
        'description' => 'Database name',
        'secret' => false,
    ],
    'DB_USERNAME' => [
        'type' => 'string',
        'default' => 'root',
        'description' => 'Database username',
        'secret' => true,
    ],
    'DB_PASSWORD' => [
        'type' => 'string',
        'default' => null,
        'description' => 'Database password',
        'secret' => true,
    ],

    // Multi-Tenancy
    'MULTITENANT_ENABLED' => [
        'type' => 'boolean',
        'default' => true,
        'description' => 'Enable multi-tenant isolation',
        'secret' => false,
    ],
    'MULTITENANT_STRICT_MODE' => [
        'type' => 'boolean',
        'default' => false,
        'description' => 'Fail queries that lack company_id scoping',
        'secret' => false,
    ],
    'DEFAULT_COMPANY_ID' => [
        'type' => 'integer',
        'default' => 1,
        'description' => 'Default company ID for single-tenant migrations',
        'secret' => false,
    ],

    // Cache Configuration
    'CACHE_DRIVER' => [
        'type' => 'enum',
        'enum' => ['file', 'redis', 'memcached', 'database'],
        'default' => 'file',
        'description' => 'Cache driver',
        'secret' => false,
    ],
    'CACHE_TTL' => [
        'type' => 'integer',
        'default' => 3600,
        'description' => 'Default cache TTL in seconds',
        'secret' => false,
    ],

    // Queue Configuration
    'QUEUE_CONNECTION' => [
        'type' => 'enum',
        'enum' => ['sync', 'database', 'redis', 'beanstalkd'],
        'default' => 'sync',
        'description' => 'Queue driver (sync = synchronous for testing)',
        'secret' => false,
    ],
    'QUEUE_RETRY_ATTEMPTS' => [
        'type' => 'integer',
        'default' => 3,
        'description' => 'Number of times to retry failed queue jobs',
        'secret' => false,
    ],

    // API Configuration
    'API_RATE_LIMIT_PER_MINUTE' => [
        'type' => 'integer',
        'default' => 60,
        'description' => 'Maximum API requests per minute (global limit)',
        'secret' => false,
    ],
    'API_PAGINATION_PER_PAGE' => [
        'type' => 'integer',
        'default' => 15,
        'description' => 'Default records per page for paginated responses',
        'secret' => false,
    ],
    'API_TIMEOUT_SECONDS' => [
        'type' => 'integer',
        'default' => 30,
        'description' => 'API request timeout in seconds',
        'secret' => false,
    ],

    // Feature Flags
    'FEATURE_KNOWLEDGE_ENGINE' => [
        'type' => 'boolean',
        'default' => true,
        'description' => 'Enable Knowledge Engine feature',
        'secret' => false,
    ],
    'FEATURE_VOICE_ENGINE' => [
        'type' => 'boolean',
        'default' => false,
        'description' => 'Enable Voice Engine feature',
        'secret' => false,
    ],
    'FEATURE_BOOKING_ENGINE' => [
        'type' => 'boolean',
        'default' => true,
        'description' => 'Enable Booking Engine feature',
        'secret' => false,
    ],
    'FEATURE_COMMERCE' => [
        'type' => 'boolean',
        'default' => true,
        'description' => 'Enable Commerce/E-commerce features',
        'secret' => false,
    ],
    'FEATURE_WORKCORE' => [
        'type' => 'boolean',
        'default' => true,
        'description' => 'Enable WorkCore ERP features',
        'secret' => false,
    ],

    // External API Keys
    'OPENAI_API_KEY' => [
        'type' => 'string',
        'default' => null,
        'description' => 'OpenAI API key for ChatGPT integration',
        'secret' => true,
    ],
    'ELEVENLABS_API_KEY' => [
        'type' => 'string',
        'default' => null,
        'description' => 'ElevenLabs API key for text-to-speech',
        'secret' => true,
    ],

    // Messaging Services
    'WHATSAPP_API_URL' => [
        'type' => 'url',
        'default' => 'https://api.whatsapp.com',
        'description' => 'WhatsApp Business API endpoint',
        'secret' => false,
    ],
    'WHATSAPP_ACCESS_TOKEN' => [
        'type' => 'string',
        'default' => null,
        'description' => 'WhatsApp Business API access token',
        'secret' => true,
    ],
    'TELEGRAM_BOT_TOKEN' => [
        'type' => 'string',
        'default' => null,
        'description' => 'Telegram bot token for messaging',
        'secret' => true,
    ],

    // Security
    'APP_KEY' => [
        'type' => 'string',
        'default' => null,
        'description' => 'Application encryption key (base64 encoded)',
        'secret' => true,
    ],
    'ENCRYPTION_CIPHER' => [
        'type' => 'enum',
        'enum' => ['AES-128-CBC', 'AES-256-CBC'],
        'default' => 'AES-256-CBC',
        'description' => 'Encryption cipher algorithm',
        'secret' => false,
    ],

    // Monitoring
    'SENTRY_DSN' => [
        'type' => 'url',
        'default' => null,
        'description' => 'Sentry error tracking DSN',
        'secret' => true,
    ],
    'ENABLE_QUERY_LOGGING' => [
        'type' => 'boolean',
        'default' => false,
        'description' => 'Log all database queries (development only)',
        'secret' => false,
    ],
    'ENABLE_PERFORMANCE_MONITORING' => [
        'type' => 'boolean',
        'default' => false,
        'description' => 'Enable performance monitoring and profiling',
        'secret' => false,
    ],
];
