<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Events\TeamChat;

use App\Extensions\Chatbot\System\Models\TeamChat\TeamMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TeamMessageCreated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly TeamMessage $message) {}

    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;
        return [new PrivateChannel('chatbot.team.' . $conversation->tenant_id . '.conversation.' . $conversation->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'team.message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'uuid' => $this->message->uuid,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->message->user_id,
            'body' => $this->message->body,
            'message' => $this->message->body,
            'content' => $this->message->body,
            'conversation_uuid' => $this->message->conversation?->uuid,
            'message_type' => $this->message->message_type,
            'version' => $this->message->version,
            'created_at' => $this->message->created_at?->toISOString(),
        ];
    }
}
