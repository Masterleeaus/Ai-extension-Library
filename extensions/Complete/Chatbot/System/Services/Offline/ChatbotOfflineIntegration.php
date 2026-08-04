<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use App\Extensions\Chatbot\System\InteractionEngine\LocalBrain\LocalBrainFacade;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotSyncOperation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class ChatbotOfflineIntegration
{
    protected LocalBrainFacade $localBrain;
    protected const MEMORY_TABLE = 'local_intelligence_memories';
    protected const SYNC_QUEUE_TABLE = 'ext_chatbot_sync_operations';

    public function __construct(LocalBrainFacade $localBrain)
    {
        $this->localBrain = $localBrain;
    }

    public function processMessageOffline(
        Authenticatable $user,
        string $message,
        int $conversationId,
        array $context = []
    ): array {
        $conversation = ChatbotConversation::findOrFail($conversationId);

        // Build LocalBrain context
        $relevantMemories = $this->getRelevantMemories($user->getAuthIdentifier(), $conversationId);
        $localBrainContext = [
            'tenant_id' => $context['tenant_id'] ?? auth()->user()->tenant_id,
            'user_id' => $user->getAuthIdentifier(),
            'conversation_id' => $conversationId,
            'subject_id' => $context['subject_id'] ?? null,
            'previous_messages' => $relevantMemories,
        ];

        // Process through LocalBrain for intent classification and reasoning
        $result = $this->localBrain->processMessage(
            message: $message,
            context: $localBrainContext,
            memories: $relevantMemories
        );

        // Store the operation for sync when online
        $this->queueMessageForSync($user, $conversationId, $message, $result);

        // Store in local memory for future ranking
        $this->storeInLocalMemory($user, $conversationId, $message, $result);

        return [
            'intent' => $result['intent'] ?? 'unknown',
            'confidence' => $result['confidence'] ?? 0.0,
            'entities' => $result['entities'] ?? [],
            'response' => $result['response'] ?? 'Unable to process message offline',
            'persona_signals' => $result['persona_signals'] ?? [],
            'decision' => $result['decision'] ?? null,
            'reasoning_trace' => $result['reasoning'] ?? [],
            'cloud_used' => false,
            'processed_at' => now()->toIso8601String(),
            'memories_ranked' => count($relevantMemories),
        ];
    }

    protected function getRelevantMemories(string $userId, int $conversationId): array
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        if (!$tenantId) {
            return [];
        }

        return DB::table(self::MEMORY_TABLE)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['message', 'response', 'intent', 'confidence', 'created_at'])
            ->toArray();
    }

    protected function queueMessageForSync(
        Authenticatable $user,
        int $conversationId,
        string $message,
        array $result
    ): void {
        $tenantId = auth()->user()->tenant_id ?? null;
        if (!$tenantId) {
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
                'intent' => $result['intent'] ?? null,
                'confidence' => $result['confidence'] ?? 0.0,
                'entities' => $result['entities'] ?? [],
                'response' => $result['response'] ?? null,
            ],
            'status' => 'local-only',
            'client_created_at' => now(),
        ]);
    }

    protected function storeInLocalMemory(
        Authenticatable $user,
        int $conversationId,
        string $message,
        array $result
    ): void {
        $tenantId = auth()->user()->tenant_id ?? null;
        if (!$tenantId) {
            return;
        }

        DB::table(self::MEMORY_TABLE)->insert([
            'tenant_id' => $tenantId,
            'user_id' => $user->getAuthIdentifier(),
            'conversation_id' => $conversationId,
            'message' => $message,
            'response' => $result['response'] ?? null,
            'intent' => $result['intent'] ?? null,
            'confidence' => $result['confidence'] ?? 0.0,
            'entities' => json_encode($result['entities'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function syncOnlineOperations(string $userId): array
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        if (!$tenantId) {
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
                // Attempt to sync to cloud
                // For now, mark as synced since we're in offline mode
                $operation->update(['status' => 'synced', 'processed_at' => now()]);
                $synced++;
            } catch (\Exception $e) {
                $operation->update(['status' => 'failed']);
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
