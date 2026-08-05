<?php

declare(strict_types=1);

/**
 * Validation Configuration
 *
 * Global validation rules and messages.
 */

return [
    // String field validation
    'strings' => [
        'name_min' => 1,
        'name_max' => 255,
        'email_max' => 254,
        'url_max' => 2048,
        'description_max' => 5000,
        'content_max' => 1000000,
    ],

    // Numeric field validation
    'numbers' => [
        'price_min' => 0,
        'price_precision' => 2,
        'quantity_min' => 0,
        'quantity_max' => 999999,
        'discount_min' => 0,
        'discount_max' => 100,
    ],

    // File uploads
    'files' => [
        'max_size_mb' => 100,
        'max_size_image_mb' => 10,
        'max_size_video_mb' => 500,
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
        'allowed_video_types' => ['mp4', 'webm', 'avi', 'mov'],
    ],

    // Email validation
    'email' => [
        'valid_tlds_only' => true,
        'check_mx' => false,
        'check_smtp' => false,
    ],

    // Password validation
    'password' => [
        'min' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => false,
    ],

    // URL validation
    'urls' => [
        'allowed_protocols' => ['http', 'https'],
        'max_length' => 2048,
    ],

    // Common rules
    'rules' => [
        'name' => 'required|string|min:1|max:255',
        'email' => 'required|email|max:254',
        'password' => 'required|string|min:8|confirmed',
        'password_change' => 'required|string|min:8|different:current_password',
        'url' => 'required|url|max:2048',
        'description' => 'nullable|string|max:5000',
        'status' => 'required|in:active,inactive,pending,archived',
        'price' => 'required|numeric|min:0|max:999999.99',
    ],

    // Error messages
    'messages' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'min' => 'The :attribute must be at least :min characters.',
        'max' => 'The :attribute must not exceed :max characters.',
        'unique' => 'The :attribute already exists.',
        'confirmed' => 'The :attribute confirmation does not match.',
    ],

    // Field-specific messages
    'custom_messages' => [
        'email.unique' => 'This email address is already registered.',
        'password.min' => 'Your password must be at least 8 characters long.',
        'password.confirmed' => 'The password confirmation does not match.',
    ],
];
