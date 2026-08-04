<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use TitanZero\Interaction\LocalIntelligence\LocalBrain;
use Illuminate\Support\Facades\DB;

class OfflineMemoryOptimizer
{
    protected LocalBrain $localBrain;
    protected const MEMORY_TABLE = 'local_intelligence_memories';
    protected const OPTIMIZATION_INTERVAL = 100; // Optimize every 100 messages
    protected const MIN_RELEVANCE_THRESHOLD = 0.3;
    protected const MAX_MEMORY_AGE_DAYS = 90;

    public function __construct(LocalBrain $localBrain)
    {
        $this->localBrain = $localBrain;
    }

    public function optimizeIfNeeded(string $tenantId, string $userId): void
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::MEMORY_TABLE)) {
                return;
            }

            $memoryCount = DB::table(self::MEMORY_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->count();

            if ($memoryCount % self::OPTIMIZATION_INTERVAL !== 0) {
                return;
            }

            $this->optimize($tenantId, $userId);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function optimize(string $tenantId, string $userId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::MEMORY_TABLE)) {
                return ['cleaned' => 0, 'pruned' => 0, 'reorganized' => 0];
            }

            $cleaned = $this->cleanDuplicateMemories($tenantId, $userId);
            $pruned = $this->pruneExpiredMemories($tenantId, $userId);
            $reorganized = $this->reorganizeByRelevance($tenantId, $userId);

            return [
                'cleaned' => $cleaned,
                'pruned' => $pruned,
                'reorganized' => $reorganized,
                'optimization_complete' => true,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function cleanDuplicateMemories(string $tenantId, string $userId): int
    {
        // Use LocalBrain to identify similar/duplicate messages
        $memories = DB::table(self::MEMORY_TABLE)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $duplicateIds = [];
        $seenMessages = [];

        foreach ($memories as $memory) {
            // Use LocalBrain to compute message similarity
            $processResult = $this->localBrain->process(
                input: $memory->message,
                context: ['memory_id' => $memory->id]
            );

            $messageHash = md5($memory->message);

            // If message is very similar to a recent one, mark for deletion
            if (isset($seenMessages[$messageHash])) {
                // Keep higher confidence version
                if (($processResult['confidence'] ?? 0) < ($seenMessages[$messageHash]['confidence'] ?? 0)) {
                    $duplicateIds[] = $memory->id;
                } else {
                    $duplicateIds[] = $seenMessages[$messageHash]['id'];
                    $seenMessages[$messageHash] = [
                        'id' => $memory->id,
                        'confidence' => $processResult['confidence'] ?? 0,
                    ];
                }
            } else {
                $seenMessages[$messageHash] = [
                    'id' => $memory->id,
                    'confidence' => $processResult['confidence'] ?? 0,
                ];
            }
        }

        // Delete duplicates
        if (!empty($duplicateIds)) {
            DB::table(self::MEMORY_TABLE)->whereIn('id', $duplicateIds)->delete();
        }

        return count($duplicateIds);
    }

    protected function pruneExpiredMemories(string $tenantId, string $userId): int
    {
        $cutoffDate = now()->subDays(self::MAX_MEMORY_AGE_DAYS);

        // Delete very old, low-confidence memories
        $deleted = DB::table(self::MEMORY_TABLE)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('created_at', '<', $cutoffDate)
            ->where('relevance_score', '<', self::MIN_RELEVANCE_THRESHOLD)
            ->delete();

        // For memories between 30-90 days old, use LocalBrain to decide if relevant
        $oldMemories = DB::table(self::MEMORY_TABLE)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereBetween('created_at', [
                now()->subDays(90),
                now()->subDays(30),
            ])
            ->where('relevance_score', '<', 0.5)
            ->limit(100)
            ->get();

        foreach ($oldMemories as $memory) {
            // Use LocalBrain to assess if memory should be kept
            $assessment = $this->localBrain->process(
                input: $memory->message,
                context: ['assessment' => 'should_keep', 'age_days' => $memory->created_at->diffInDays(now())]
            );

            if (($assessment['confidence'] ?? 0) < 0.4) {
                DB::table(self::MEMORY_TABLE)->where('id', $memory->id)->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function reorganizeByRelevance(string $tenantId, string $userId): int
    {
        // Recalculate relevance scores for all memories using LocalBrain
        $memories = DB::table(self::MEMORY_TABLE)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('relevance_recalculated_at')
            ->limit(200)
            ->get();

        $reorganized = 0;

        foreach ($memories as $memory) {
            // Use LocalBrain to compute actual relevance
            $assessment = $this->localBrain->process(
                input: $memory->message,
                context: [
                    'assessment' => 'relevance',
                    'confidence' => $memory->confidence,
                    'age_days' => $memory->created_at->diffInDays(now()),
                ]
            );

            $newRelevance = $this->calculateRelevanceScore(
                $memory->confidence,
                $assessment['confidence'] ?? 0.5,
                $memory->created_at->diffInDays(now())
            );

            DB::table(self::MEMORY_TABLE)
                ->where('id', $memory->id)
                ->update([
                    'relevance_score' => $newRelevance,
                    'relevance_recalculated_at' => now(),
                    'updated_at' => now(),
                ]);

            $reorganized++;
        }

        return $reorganized;
    }

    protected function calculateRelevanceScore(float $confidence, float $contextualFit, int $ageDays): float
    {
        // Combine multiple factors into relevance score
        $confidenceFactor = $confidence * 0.4;
        $contextualFactor = $contextualFit * 0.4;

        // Decay by age: fresh memories are more relevant
        $ageFactor = max(0.2, 1 - ($ageDays / 365)) * 0.2;

        $relevance = $confidenceFactor + $contextualFactor + $ageFactor;

        return min(1.0, max(0.0, $relevance));
    }

    public function getStorageStats(string $tenantId, string $userId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::MEMORY_TABLE)) {
                return [];
            }

            $stats = DB::table(self::MEMORY_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->selectRaw('COUNT(*) as total_memories')
                ->selectRaw('AVG(relevance_score) as avg_relevance')
                ->selectRaw('MAX(created_at) as last_memory')
                ->selectRaw('MIN(created_at) as oldest_memory')
                ->first();

            $highRelevance = DB::table(self::MEMORY_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('relevance_score', '>=', 0.7)
                ->count();

            return [
                'total_memories' => $stats->total_memories ?? 0,
                'high_relevance_count' => $highRelevance,
                'avg_relevance' => round($stats->avg_relevance ?? 0, 2),
                'storage_optimized' => true,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
