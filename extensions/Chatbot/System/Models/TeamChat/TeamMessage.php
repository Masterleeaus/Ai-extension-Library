<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models\TeamChat;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMessage extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'ext_chatbot_team_messages';

    protected $fillable = [
        'uuid', 'tenant_id', 'conversation_id', 'user_id', 'body', 'message_type',
        'reply_to_id', 'attachment_path', 'attachment_name', 'attachment_type',
        'attachment_size', 'version', 'sync_sequence', 'originating_device_id',
        'edited_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'attachment_size' => 'integer', 'version' => 'integer',
        'sync_sequence' => 'integer', 'edited_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TeamConversation::class, 'conversation_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(TeamMessageRead::class, 'message_id');
    }
}
