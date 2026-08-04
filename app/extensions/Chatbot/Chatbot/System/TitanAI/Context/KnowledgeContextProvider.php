<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Context;

use App\Extensions\Chatbot\System\TitanAI\Contracts\KnowledgeContextProviderContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;

final class KnowledgeContextProvider implements KnowledgeContextProviderContract
{
    public function context(TitanAIRequest $request): array
    {
        $items = $request->payload['knowledge_context'] ?? [];
        if (is_string($items)) {
            $items = trim($items) === '' ? [] : [$items];
        }
        if (! is_array($items)) return [];

        $items = array_slice(array_values(array_filter($items, static fn ($item): bool => is_scalar($item) && trim((string) $item) !== '')), 0, 20);
        if ($items === []) return [];

        return [[
            'role' => 'system',
            'content' => "Authoritative knowledge context:\n- ".implode("\n- ", array_map(static fn ($item): string => trim((string) $item), $items)),
        ]];
    }
}
