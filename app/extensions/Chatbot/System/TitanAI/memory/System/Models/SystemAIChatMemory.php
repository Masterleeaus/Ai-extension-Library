<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Models;

use Illuminate\Database\Eloquent\Model;

final class SystemAIChatMemory extends Model
{
    protected $table = 'system_ai_chat_memories';

    protected $fillable = [
        'content',
        'source',
        'metadata',
        'consented_at',
        'last_recalled_at',
        'expires_at',
        'importance',
        'confidence',
        'recall_count',
    ];

    protected $casts = [
        'content' => 'array',
        'source' => 'array',
        'metadata' => 'array',
        'consented_at' => 'datetime',
        'last_recalled_at' => 'datetime',
        'expires_at' => 'datetime',
        'importance' => 'float',
        'confidence' => 'float',
        'recall_count' => 'integer',
    ];
}
