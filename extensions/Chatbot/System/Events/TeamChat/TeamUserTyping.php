<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Events\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TeamUserTyping implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public TeamConversation $conversation, public int $userId, public bool $typing)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chatbot.team.'.$this->conversation->tenant_id.'.conversation.'.$this->conversation->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'team.user.typing';
    }

    public function broadcastWith(): array
    {
        return ['conversation_uuid' => $this->conversation->uuid, 'user_id' => $this->userId, 'typing' => $this->typing];
    }
}
