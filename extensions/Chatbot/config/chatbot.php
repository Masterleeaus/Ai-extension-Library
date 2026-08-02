<?php

return [
    'version' => 1.0,
    'avatars' => [
        'avatar-1.png',
        'avatar-2.png',
        'avatar-3.png',
        'avatar-4.png',
        'avatar-5.png',
    ],
    'notification_enabled' => env('CHATBOT_NOTIFICATION_ENABLED', true),
    'generative_ui' => [
        'enabled' => env('CHATBOT_GENERATIVE_UI_ENABLED', true),
        'natural_language_triggers' => env('CHATBOT_GENERATIVE_UI_NATURAL_TRIGGERS', true),
        'preferred_chat_elements' => 50,
        'max_elements' => 250,
        'authority' => 'presentation-only',
    ],
];
