<?php
return [
    'enabled' => env('CHAT_SHARE_ENABLED', true),
    'default_expiry_hours' => (int) env('CHAT_SHARE_DEFAULT_EXPIRY_HOURS', 168),
    'maximum_expiry_hours' => (int) env('CHAT_SHARE_MAXIMUM_EXPIRY_HOURS', 720),
    'rate_limit' => env('CHAT_SHARE_RATE_LIMIT', '20,1'),
];
