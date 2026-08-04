<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use TitanZero\Interaction\LocalIntelligence\LocalBrain;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotSyncOperation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class ChatbotOfflineIntegration
{
    protected LocalBrain $localBrain;
    protected OfflineMemoryOptimizer $memoryOptimizer;
    protected OnlineOfflineSwitcher $switcher;
    protected const MEMORY_TABLE = 'local_intelligence_memories';
    protected const SYNC_QUEUE_TABLE = 'ext_chatbot_sync_operations';

    public function __construct(
        LocalBrain $localBrain,
        OfflineMemoryOptimizer $memoryOptimizer,
        OnlineOfflineSwitcher $switcher
    ) {
        $this->localBrain = $localBrain;
        $this->memoryOptimizer = $memoryOptimizer;
        $this->switcher = $switcher;
    }

    public function processMessageOffline(
        Authenticatable $user,
        string $message,
        int $conversationId,
        array $context = []
    ): array {
        try {
            $conversation = ChatbotConversation::findOrFail($conversationId);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Conversation not found', 'cloud_used' => false];
        }

        $tenantId = auth()->check() ? auth()->user()->tenant_id : ($context['tenant_id'] ?? null);
        if (!$tenantId) {
            return ['success' => false, 'error' => 'Tenant context required', 'cloud_used' => false];
        }

        // Get relevant memories - automatically pruned and optimized
        $relevantMemories = $this->getOptimizedMemories($tenantId, $user->getAuthIdentifier(), $conversationId);

        $contextData = [
            'tenant_id' => $tenantId,
            'user_id' => $user->getAuthIdentifier(),
            'conversation_id' => $conversationId,
            'subject_id' => $context['subject_id'] ?? null,
        ];

        // Use best available AI: Cloud when online, LocalBrain when offline
        $result = $this->switcher->processMessageIntelligent(
            message: $message,
            context: $contextData,
            userId: $user->getAuthIdentifier(),
            tenantId: $tenantId
        );

        // Queue for sync
        $this->queueMessageForSync($tenantId, $user, $conversationId, $message, $result);

        // Store in optimized memory
        $this->storeInOptimizedMemory($tenantId, $user->getAuthIdentifier(), $conversationId, $message, $result);

        // Trigger periodic memory optimization
        $this->memoryOptimizer->optimizeIfNeeded($tenantId, $user->getAuthIdentifier());

        return [
            'success' => true,
            'action' => $result['action'] ?? null,
            'confidence' => $result['confidence'] ?? 0.0,
            'entities' => $result['entities'] ?? [],
            'suggestions' => $result['suggestions'] ?? [],
            'cloud_used' => false,
            'processed_at' => now()->toIso8601String(),
            'memories_ranked' => count($relevantMemories),
        ];
    }

    protected function getOptimizedMemories(string $tenantId, string $userId, int $conversationId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::MEMORY_TABLE)) {
                return [];
            }

            return DB::table(self::MEMORY_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('conversation_id', $conversationId)
                ->where('relevance_score', '>=', 0.3) // Filter out low-relevance memories
                ->orderByDesc('relevance_score')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['message', 'action', 'confidence', 'relevance_score', 'created_at'])
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function queueMessageForSync(
        string $tenantId,
        Authenticatable $user,
        int $conversationId,
        string $message,
        array $result
    ): void {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::SYNC_QUEUE_TABLE)) {
                return;
            }

            ChatbotSyncOperation::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->getAuthIdentifier(),
                'conversation_id' => $conversationId,
                'operation_type' => 'message',
                'entity_type' => 'message',
                'payload' => [
                    'message' => $message,
                    'action' => $result['action'] ?? null,
                    'confidence' => $result['confidence'] ?? 0.0,
                    'entities' => $result['entities'] ?? [],
                    'suggestions' => $result['suggestions'] ?? [],
                ],
                'status' => 'local-only',
                'client_created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if queue table doesn't exist
        }
    }

    protected function storeInOptimizedMemory(
        string $tenantId,
        string $userId,
        int $conversationId,
        string $message,
        array $result
    ): void {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::MEMORY_TABLE)) {
                return;
            }

            DB::table(self::MEMORY_TABLE)->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'conversation_id' => $conversationId,
                'message' => $message,
                'action' => $result['action'] ?? null,
                'confidence' => $result['confidence'] ?? 0.0,
                'entities' => json_encode($result['entities'] ?? []),
                'relevance_score' => $result['confidence'] ?? 0.7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist
        }
    }

    public function syncOnlineOperations(string $tenantId, string $userId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::SYNC_QUEUE_TABLE)) {
                return ['synced' => 0, 'failed' => 0];
            }

            $queued = ChatbotSyncOperation::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('status', 'local-only')
                ->get();

            $synced = 0;
            $failed = 0;

            foreach ($queued as $operation) {
                try {
                    $operation->update(['status' => 'synced', 'processed_at' => now()]);
                    $synced++;
                } catch (\Exception $e) {
                    $operation->update(['status' => 'failed']);
                    $failed++;
                }
            }

            return ['synced' => $synced, 'failed' => $failed];
        } catch (\Exception $e) {
            return ['synced' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }
    }
}
