<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models\TeamChat;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamConversation extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'ext_chatbot_team_conversations';

    protected $fillable = [
        'uuid', 'tenant_id', 'name', 'slug', 'type', 'avatar', 'description', 'category',
        'owner_id', 'visibility', 'posting_policy', 'is_announcement',
        'linked_entity_type', 'linked_entity_id', 'channel_archived_at',
        'is_archived', 'version', 'sync_sequence',
        'originating_device_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_announcement' => 'boolean',
        'channel_archived_at' => 'datetime',
        'version' => 'integer',
        'sync_sequence' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TeamConversationParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TeamMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(TeamMessage::class, 'conversation_id')->latestOfMany();
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->whereNull('left_at')->exists();
    }

    public function isChannel(): bool
    {
        return $this->type === 'channel';
    }

    public function isLinkedRoom(): bool
    {
        return $this->isChannel() && $this->linked_entity_type !== null && $this->linked_entity_id !== null;
    }

    public function participantFor(int $userId): ?TeamConversationParticipant
    {
        return $this->participants()->where('user_id', $userId)->whereNull('left_at')->first();
    }
}
