<?php

namespace App\Extensions\Chatbot\System\Services\StaffInbox;

use App\Extensions\Chatbot\System\Http\Resources\Api\ChatbotHistoryResource;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Services\StaffInbox\Contracts\AblyService;

class ChatbotForFrameEventAbly extends AblyService
{
    public static string $chanel = 'conversation-session-';

    public static function dispatch(ChatbotHistory $chatbotHistory, string $sessionId): void
    {
        $ably = self::ablyRest();

        $channel = $ably->channels->get(
            self::$chanel . $sessionId
        );

        $channel->publish('new-message', [
            'sessionId'      => $sessionId,
            'conversationId' => $chatbotHistory->getAttribute('conversation_id'),
            'history'        => ChatbotHistoryResource::make($chatbotHistory)->jsonSerialize(),
        ]);
    }
}
