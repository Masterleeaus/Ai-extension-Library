<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Memory;

use TitanAI\Hybrid\Diagnostics\TitanAIDiagnostics;
use TitanAI\Hybrid\Memory\Enums\MemoryScope;
use TitanAI\Hybrid\Memory\Services\UnifiedMemoryRepository;
use InvalidArgumentException;
use Throwable;

/**
 * Best-effort mirror from the legacy AI Agent memory table into shared memory.
 * The native table remains authoritative until the host migration is proven.
 */
final class UnifiedMemoryBridge
{
    public function __construct(
        private readonly UnifiedMemoryRepository $memory,
        private readonly TitanAIDiagnostics $diagnostics,
    ) {}

    public function remember(int $userId, int|string $memoryId, string $memory): bool
    {
        return $this->attempt('remember', function () use ($userId, $memoryId, $memory): void {
            $this->memory->store(
                'user',
                $userId,
                MemoryScope::USER,
                $this->key($memoryId),
                ['legacy_id' => (string) $memoryId, 'memory' => $memory],
                source: 'aiagent',
            );
        }, ['entity_type' => 'user', 'scope' => MemoryScope::USER->value, 'source' => 'aiagent']);
    }

    public function forget(int $userId, int|string $memoryId): bool
    {
        return $this->attempt('forget', function () use ($userId, $memoryId): void {
            $this->memory->forget('user', $userId, MemoryScope::USER, $this->key($memoryId));
        }, ['entity_type' => 'user', 'scope' => MemoryScope::USER->value, 'source' => 'aiagent'], 'forgotten');
    }

    public function forgetAll(int $userId): bool
    {
        return $this->attempt('forget_all', function () use ($userId): void {
            $this->memory->forgetAllForEntity('user', $userId, MemoryScope::USER, 'aiagent.memory.');
        }, ['entity_type' => 'user', 'scope' => MemoryScope::USER->value, 'source' => 'aiagent'], 'forgotten');
    }

    private function key(int|string $memoryId): string
    {
        $memoryId = trim((string) $memoryId);
        if ($memoryId === '') {
            throw new InvalidArgumentException('AI Agent memory bridge requires a native memory id.');
        }
        return 'aiagent.memory.' . $memoryId;
    }

    /** @param array<string,mixed> $context */
    private function attempt(string $operation, callable $callback, array $context, string $success = 'bridged'): bool
    {
        if (! (bool) config('titanai.features.shared_memory', true)
            || ! (bool) config('titanai.extensions.aiagent.enabled', true)
            || ! (bool) config('titanai.extensions.aiagent.memory_bridge.enabled', true)) {
            return false;
        }

        try {
            $callback();
            $this->diagnostics->recordMemory($success, $operation, $context);
            return true;
        } catch (Throwable $exception) {
            $this->diagnostics->recordMemory('failed', $operation, $context, $exception);
            if ((bool) config('titanai.extensions.aiagent.memory_bridge.strict', false)) {
                throw $exception;
            }
            return false;
        }
    }
}
