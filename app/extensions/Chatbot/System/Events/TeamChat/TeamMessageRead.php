<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Events\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TeamMessageRead implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public TeamMessage $message, public int $userId, public string $readAt)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chatbot.team.'.$this->message->tenant_id.'.conversation.'.$this->message->conversation->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'team.message.read';
    }

    public function broadcastWith(): array
    {
        return ['message_uuid' => $this->message->uuid, 'user_id' => $this->userId, 'read_at' => $this->readAt];
    }
}
