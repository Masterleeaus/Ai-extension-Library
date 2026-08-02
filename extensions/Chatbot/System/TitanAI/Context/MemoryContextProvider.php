<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Context;

use App\Extensions\Chatbot\System\TitanAI\Contracts\MemoryContextProviderContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;

final class MemoryContextProvider implements MemoryContextProviderContract
{
    public function context(TitanAIRequest $request): array
    {
        $messages = array_values(array_filter($request->messages, static fn ($item): bool => is_array($item) && isset($item['role'])));
        $limit = max(1, (int) config('titan-ai.memory_message_limit', 30));
        $messages = array_slice($messages, -$limit);

        $memory = $request->payload['memory'] ?? [];
        if (is_string($memory) && trim($memory) !== '') {
            array_unshift($messages, ['role' => 'system', 'content' => 'Relevant memory: '.trim($memory)]);
        } elseif (is_array($memory) && $memory !== []) {
            array_unshift($messages, ['role' => 'system', 'content' => 'Relevant memory: '.json_encode($memory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }

        return $messages;
    }
}
