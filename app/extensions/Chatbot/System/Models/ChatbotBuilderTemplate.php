<?php

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotBuilderTemplate extends Model
{
    protected $table = 'ext_chatbot_builder_templates';

    protected $fillable = [
        'tenant_id',
        'template_name',
        'template_category',
        'template_config',
        'customization',
        'theme_settings',
        'description',
        'is_system_template',
        'usage_count',
    ];

    protected $casts = [
        'template_config' => 'json',
        'customization' => 'json',
        'theme_settings' => 'json',
        'is_system_template' => 'boolean',
    ];
}
