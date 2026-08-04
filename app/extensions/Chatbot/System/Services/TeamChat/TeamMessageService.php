<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services\TeamChat;

use App\Extensions\Chatbot\System\Events\TeamChat\TeamMessageCreated;
use App\Extensions\Chatbot\System\Events\TeamChat\TeamMessageRead as TeamMessageReadEvent;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamMessage;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamMessageRead;
use App\Extensions\Chatbot\System\Policies\TeamChat\TeamConversationPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TeamMessageService
{
    public function send(TeamConversation $conversation, int $tenantId, int $actorId, string $body, ?string $localUuid = null, ?string $deviceId = null): TeamMessage
    {
        abort_unless($conversation->tenant_id === $tenantId && $conversation->hasParticipant($actorId), 403);
        abort_unless((new TeamConversationPolicy())->canPost($conversation, $actorId), 403);

        $message = DB::transaction(function () use ($conversation, $tenantId, $actorId, $body, $localUuid, $deviceId): TeamMessage {
            $message = TeamMessage::query()->firstOrCreate(
                ['uuid' => $localUuid ?: (string) Str::uuid(), 'tenant_id' => $tenantId],
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $actorId,
                    'body' => trim($body),
                    'message_type' => 'text',
                    'originating_device_id' => $deviceId,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]
            );

            $conversation->increment('version');
            $conversation->touch();

            return $message;
        });

        event(new TeamMessageCreated($message));

        return $message;
    }

    public function markRead(TeamMessage $message, int $tenantId, int $actorId): TeamMessageRead
    {
        abort_unless($message->tenant_id === $tenantId && $message->conversation->hasParticipant($actorId), 403);

        $receipt = TeamMessageRead::query()->firstOrCreate(
            ['message_id' => $message->id, 'user_id' => $actorId],
            ['tenant_id' => $tenantId, 'read_at' => now()]
        );

        event(new TeamMessageReadEvent($message, $actorId, $receipt->read_at->toIso8601String()));

        return $receipt;
    }
}
