<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models\TeamChat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamConversationParticipant extends Model
{
    use SoftDeletes;

    protected $table = 'ext_chatbot_team_conversation_participants';

    protected $fillable = [
        'conversation_id', 'tenant_id', 'user_id', 'role', 'last_read_at',
        'joined_at', 'left_at', 'is_muted', 'is_pinned', 'archived_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime', 'joined_at' => 'datetime', 'left_at' => 'datetime',
        'archived_at' => 'datetime', 'is_muted' => 'boolean', 'is_pinned' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TeamConversation::class, 'conversation_id');
    }
}
