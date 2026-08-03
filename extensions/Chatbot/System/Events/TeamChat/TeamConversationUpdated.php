<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Events\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TeamConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public TeamConversation $conversation, public string $change, public int $actorId)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chatbot.team.'.$this->conversation->tenant_id.'.conversation.'.$this->conversation->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'team.conversation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_uuid' => $this->conversation->uuid,
            'version' => $this->conversation->version,
            'change' => $this->change,
            'actor_id' => $this->actorId,
            'updated_at' => optional($this->conversation->updated_at)->toIso8601String(),
        ];
    }
}
