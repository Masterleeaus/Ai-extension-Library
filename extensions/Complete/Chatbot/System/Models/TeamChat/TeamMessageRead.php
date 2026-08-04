<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models\TeamChat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMessageRead extends Model
{
    protected $table = 'ext_chatbot_team_message_reads';

    protected $fillable = ['message_id', 'tenant_id', 'user_id', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TeamMessage::class, 'message_id');
    }
}
