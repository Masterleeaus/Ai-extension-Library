<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use TitanZero\Interaction\LocalIntelligence\LocalBrain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OnlineOfflineSwitcher
{
    protected LocalBrain $localBrain;
    protected const ONLINE_CHECK_INTERVAL = 60; // Check every 60 seconds
    protected const ONLINE_CHECK_URL = 'https://api.anthropic.com/health';
    protected const CACHE_KEY = 'chatbot.online_status';

    public function __construct(LocalBrain $localBrain)
    {
        $this->localBrain = $localBrain;
    }

    /**
     * Determine if system is online and can use cloud models
     */
    public function isOnline(): bool
    {
        try {
            // Check cache first
            $cached = cache()->get(self::CACHE_KEY);
            if ($cached !== null && is_bool($cached)) {
                return $cached;
            }

            // Attempt connection
            $response = Http::timeout(5)->head(self::ONLINE_CHECK_URL);
            $isOnline = $response->successful();

            // Cache for ONLINE_CHECK_INTERVAL seconds
            cache()->put(self::CACHE_KEY, $isOnline, self::ONLINE_CHECK_INTERVAL);

            return $isOnline;
        } catch (\Exception $e) {
            // Assume offline on any error
            cache()->put(self::CACHE_KEY, false, self::ONLINE_CHECK_INTERVAL);
            return false;
        }
    }

    /**
     * Process message using best available AI
     * Online: Use cloud model for better reasoning
     * Offline: Use LocalBrain
     */
    public function processMessageIntelligent(
        string $message,
        array $context = [],
        string $userId = null,
        string $tenantId = null
    ): array {
        $isOnline = $this->isOnline();

        if ($isOnline && config('chatbot.use_cloud_ai_when_online', true)) {
            // Use cloud model
            return $this->processWithCloudAI($message, $context, $userId, $tenantId);
        }

        // Use offline LocalBrain
        return $this->processWithLocalBrain($message, $context);
    }

    protected function processWithCloudAI(
        string $message,
        array $context,
        ?string $userId,
        ?string $tenantId
    ): array {
        try {
            // Use cloud AI (Anthropic API) for better reasoning
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model', 'claude-3-5-sonnet-20241022'),
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
                'system' => $this->buildSystemPrompt($context),
            ]);

            if (!$response->successful()) {
                return $this->processWithLocalBrain($message, $context);
            }

            $cloudResult = $response->json();
            $result = [
                'action' => $this->extractAction($cloudResult),
                'confidence' => 0.95, // Cloud AI confidence
                'entities' => $this->extractEntities($cloudResult),
                'suggestions' => $cloudResult['content'][0]['text'] ?? '',
                'cloud_used' => true,
                'model' => config('services.anthropic.model'),
                'original_response' => $cloudResult,
            ];

            // Log for improvement training
            if ($userId && $tenantId) {
                $this->logInteractionForImprovement(
                    $tenantId,
                    $userId,
                    $message,
                    $result,
                    'cloud'
                );
            }

            return $result;
        } catch (\Exception $e) {
            // Fallback to offline
            return $this->processWithLocalBrain($message, $context);
        }
    }

    protected function processWithLocalBrain(string $message, array $context): array
    {
        $result = $this->localBrain->process(
            input: $message,
            context: $context
        );

        return array_merge($result, [
            'cloud_used' => false,
            'model' => 'localbrain-v2',
        ]);
    }

    protected function buildSystemPrompt(array $context): string
    {
        $prompt = "You are an intelligent business assistant helping with WorkCore operations.";

        if (!empty($context['action'])) {
            $prompt .= "\n\nCurrent action: {$context['action']}";
        }

        if (!empty($context['user_type'])) {
            $prompt .= "\n\nUser type: {$context['user_type']}";
        }

        if (!empty($context['business_domain'])) {
            $prompt .= "\n\nBusiness domain: {$context['business_domain']}";
        }

        return $prompt;
    }

    protected function extractAction(array $response): ?string
    {
        $content = $response['content'][0]['text'] ?? '';

        // Simple pattern matching for action extraction
        if (preg_match('/action:\s*(\w+)/i', $content, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    protected function extractEntities(array $response): array
    {
        $content = $response['content'][0]['text'] ?? '';
        $entities = [];

        // Extract common entity patterns
        if (preg_match_all('/email:\s*(\S+@\S+)/i', $content, $matches)) {
            $entities['emails'] = $matches[1];
        }

        if (preg_match_all('/phone:\s*([\d\-\+\(\)]+)/i', $content, $matches)) {
            $entities['phones'] = $matches[1];
        }

        if (preg_match_all('/amount:\s*\$?([\d\.]+)/i', $content, $matches)) {
            $entities['amounts'] = $matches[1];
        }

        return $entities;
    }

    protected function logInteractionForImprovement(
        string $tenantId,
        string $userId,
        string $message,
        array $result,
        string $modelUsed
    ): void {
        try {
            if (!DB::getSchemaBuilder()->hasTable('interaction_improvement_logs')) {
                return;
            }

            DB::table('interaction_improvement_logs')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'input_message' => $message,
                'model_used' => $modelUsed,
                'action' => $result['action'] ?? null,
                'confidence' => $result['confidence'] ?? 0.0,
                'result_payload' => json_encode($result),
                'feedback_score' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function getOnlineStatus(): array
    {
        return [
            'is_online' => $this->isOnline(),
            'ai_model' => $this->isOnline() ? 'cloud-claude' : 'localbrain-v2',
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
