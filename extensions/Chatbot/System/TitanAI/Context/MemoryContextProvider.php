<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Context;

use App\Domains\TitanAI\Diagnostics\TitanAIDiagnostics;
use App\Domains\TitanAI\Memory\Enums\MemoryScope;
use App\Domains\TitanAI\Memory\Services\UnifiedMemoryRepository;
use App\Extensions\Chatbot\System\TitanAI\Contracts\MemoryContextProviderContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;
use Throwable;

final class MemoryContextProvider implements MemoryContextProviderContract
{
    public function __construct(
        private readonly UnifiedMemoryRepository $memory,
        private readonly TitanAIDiagnostics $diagnostics,
    ) {}

    public function context(TitanAIRequest $request): array
    {
        $messages = array_values(array_filter(
            $request->messages,
            static fn ($item): bool => is_array($item) && isset($item['role']),
        ));
        $limit = max(1, (int) config('titan-ai.memory_message_limit', 30));
        $messages = array_slice($messages, -$limit);

        $relevant = [];
        $requestMemory = $request->payload['memory'] ?? [];
        if (is_string($requestMemory) && trim($requestMemory) !== '') {
            $relevant['request'] = trim($requestMemory);
        } elseif (is_array($requestMemory) && $requestMemory !== []) {
            $relevant['request'] = $requestMemory;
        }

        try {
            if (! (bool) config('titanai.features.shared_memory', true)) {
                return $this->prependMemory($messages, $relevant);
            }

            $userMemory = $this->limitEntries(
                $this->memory->allForEntity('user', $request->userId, MemoryScope::USER),
            );
            if ($userMemory !== []) {
                $relevant['user'] = $userMemory;
            }

            if ($request->workflowId !== null && trim($request->workflowId) !== '') {
                $workflowMemory = $this->limitEntries($this->memory->allForEntity(
                    'workflow',
                    $request->workflowId,
                    MemoryScope::WORKFLOW,
                ));
                if ($workflowMemory !== []) {
                    $relevant['workflow'] = $workflowMemory;
                }
            }
        } catch (Throwable $exception) {
            $this->diagnostics->recordMemory('failed', 'chatbot_context_read', [
                'entity_type' => 'user',
                'scope' => MemoryScope::USER->value,
                'source' => 'chatbot',
            ], $exception);
        }

        return $this->prependMemory($messages, $relevant);
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function limitEntries(array $values): array
    {
        $limit = max(1, (int) config('titanai.memory.context_entry_limit', 50));
        return array_slice($values, 0, $limit, true);
    }

    /** @param list<array<string,mixed>> $messages @param array<string,mixed> $relevant */
    private function prependMemory(array $messages, array $relevant): array
    {
        if ($relevant === []) return $messages;

        $encoded = json_encode($relevant, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($encoded)) {
            array_unshift($messages, ['role' => 'system', 'content' => 'Relevant memory: ' . $encoded]);
        }

        return $messages;
    }
}
