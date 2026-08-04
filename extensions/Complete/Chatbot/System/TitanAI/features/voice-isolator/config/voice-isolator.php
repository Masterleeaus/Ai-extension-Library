<?php

declare(strict_types=1);

return [
    'max_upload_bytes' => (int) env('VOICE_ISOLATOR_MAX_UPLOAD_BYTES', 25 * 1024 * 1024),
    'allowed_mime_types' => array_filter(explode(',', (string) env('VOICE_ISOLATOR_ALLOWED_MIME_TYPES', 'audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/ogg,audio/webm'))),
    'upgrade_limits' => [
        'enabled' => env('VOICE_ISOLATOR_ENABLED', true),
        'request_timeout_seconds' => (int) env('VOICE_ISOLATOR_TIMEOUT', 20),
        'max_results' => (int) env('VOICE_ISOLATOR_MAX_RESULTS', 50),
        'max_input_characters' => (int) env('VOICE_ISOLATOR_MAX_INPUT', 20000),
    ],


    'max_error_message_characters' => (int) env('VOICE_ISOLATOR_MAX_ERROR_MESSAGE_CHARACTERS', 240),
    'audit_jobs' => (bool) env('VOICE_ISOLATOR_AUDIT_JOBS', true),
    'submit_guard_milliseconds' => (int) env('VOICE_ISOLATOR_SUBMIT_GUARD_MS', 3000),
];
