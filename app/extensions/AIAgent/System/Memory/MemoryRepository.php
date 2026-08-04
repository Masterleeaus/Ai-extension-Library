<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Memory;

use App\Extensions\AIAgent\System\Models\AIAgentMemory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemoryRepository
{
    public function __construct(private readonly UnifiedMemoryBridge $unifiedMemory) {}

    /**
     * Load all memory entries for a user.
     *
     * @return Collection<int, AIAgentMemory>
     */
    public function forUser(int $userId): Collection
    {
        return AIAgentMemory::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * Load specific memory entries by their IDs.
     *
     * @param  int[]  $ids
     *
     * @return Collection<int, AIAgentMemory>
     */
    public function findByIds(int $userId, array $ids): Collection
    {
        return AIAgentMemory::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Create a new memory entry.
     */
    public function remember(int $userId, string $memory): AIAgentMemory
    {
        return DB::transaction(function () use ($userId, $memory): AIAgentMemory {
            $entry = AIAgentMemory::query()->create([
                'user_id' => $userId,
                'memory'  => $memory,
            ]);

            $this->unifiedMemory->remember($userId, (string) $entry->getKey(), $memory);

            return $entry;
        });
    }

    /**
     * Update an existing memory entry.
     */
    public function update(AIAgentMemory $memory, string $content): AIAgentMemory
    {
        return DB::transaction(function () use ($memory, $content): AIAgentMemory {
            $memory->update(['memory' => $content]);
            $this->unifiedMemory->remember((int) $memory->user_id, (string) $memory->getKey(), $content);

            return $memory;
        });
    }

    /**
     * Delete a single memory entry by model.
     */
    public function forget(AIAgentMemory $memory): void
    {
        DB::transaction(function () use ($memory): void {
            $userId = (int) $memory->user_id;
            $memoryId = (string) $memory->getKey();
            $memory->delete();
            $this->unifiedMemory->forget($userId, $memoryId);
        });
    }

    /**
     * Delete all memory for a user (used on uninstall / account deletion).
     */
    public function forgetAll(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            AIAgentMemory::query()->where('user_id', $userId)->delete();
            $this->unifiedMemory->forgetAll($userId);
        });
    }

    public function countForUser(int $userId): int
    {
        return AIAgentMemory::query()->where('user_id', $userId)->count();
    }
}
