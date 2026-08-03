<?php

return [
    'max_files_per_request' => (int) env('AI_CHAT_PRO_FILE_CHAT_MAX_FILES', 5),
    'max_file_bytes' => (int) env('AI_CHAT_PRO_FILE_CHAT_MAX_FILE_BYTES', 26214400),
    'version' => 1.0,

    'upgrade_limits' => [
        'enabled' => env('AI_CHAT_PRO_FILE_CHAT_ENABLED', true),
        'request_timeout_seconds' => (int) env('AI_CHAT_PRO_FILE_CHAT_TIMEOUT', 20),
        'max_results' => (int) env('AI_CHAT_PRO_FILE_CHAT_MAX_RESULTS', 50),
        'max_input_characters' => (int) env('AI_CHAT_PRO_FILE_CHAT_MAX_INPUT', 20000),
    ],

    'max_total_bytes' => (int) env('AI_CHAT_PRO_FILE_CHAT_MAX_TOTAL_BYTES', 25 * 1024 * 1024),
    'allowed_mime_types' => array_filter(explode(',', (string) env('AI_CHAT_PRO_FILE_CHAT_ALLOWED_MIME_TYPES', 'text/plain,text/markdown,application/pdf,application/json,text/csv'))),

    'deduplicate_by_content' => (bool) env('AI_CHAT_PRO_FILE_CHAT_DEDUPLICATE', true),
    'audit_ingestion' => (bool) env('AI_CHAT_PRO_FILE_CHAT_AUDIT_INGESTION', true),
];
