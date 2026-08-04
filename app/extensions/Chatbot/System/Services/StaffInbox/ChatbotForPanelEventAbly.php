<?php

namespace App\Extensions\Chatbot\System\Services\StaffInbox;

use App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotConversationForAblyResource;
use App\Extensions\Chatbot\System\Http\Resources\Api\ChatbotHistoryResource;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Services\StaffInbox\Contracts\AblyService;
use Exception;

class ChatbotForPanelEventAbly extends AblyService
{
    public static string $chanel = 'panel-conversation-';

    public static function dispatch(
        Chatbot $chatbot,
        ChatbotConversation $chatbotConversation,
        ?ChatbotHistory $history = null,
    ): void {

        $apiKey = self::apiKey();

        if (! $apiKey) {
            return;
        }

        $ably = self::ablyRest();

        try {
            $channel = $ably->channels->get(
                self::$chanel . $chatbot->getAttribute('user_id')
            );

            $channel->publish('conversation', [
                'userId'              => $chatbot->getAttribute('user_id'),
                'conversationId'      => $chatbotConversation->getKey(),
                'history'             => $history ? ChatbotHistoryResource::make($history)->jsonSerialize() : null,
                'chatbotConversation' => ChatbotConversationForAblyResource::make($chatbotConversation)->jsonSerialize(),
            ]);
        } catch (Exception $exception) {
            report($exception);
        }
    }
}
