<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use TitanZero\Interaction\LocalIntelligence\LocalBrain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class InteractionEngineImprover
{
    protected LocalBrain $localBrain;
    protected OnlineOfflineSwitcher $switcher;
    protected const IMPROVEMENT_LOG_TABLE = 'interaction_improvement_logs';
    protected const ANALYSIS_BATCH_SIZE = 100;

    public function __construct(LocalBrain $localBrain, OnlineOfflineSwitcher $switcher)
    {
        $this->localBrain = $localBrain;
        $this->switcher = $switcher;
    }

    /**
     * Analyze interactions and improve InteractionEngine when online
     * Uses cloud AI to identify patterns and suggest improvements
     */
    public function improveFromInteractions(string $tenantId): array
    {
        if (!$this->switcher->isOnline()) {
            return ['status' => 'offline', 'improvements_made' => 0];
        }

        try {
            $improvements = [
                'intent_improvements' => 0,
                'memory_optimizations' => 0,
                'prompt_refinements' => 0,
                'error_fixes' => 0,
                'total' => 0,
            ];

            // Analyze recent interactions
            $improvements['intent_improvements'] = $this->improveIntentClassification($tenantId);
            $improvements['memory_optimizations'] = $this->optimizeMemoryPatterns($tenantId);
            $improvements['prompt_refinements'] = $this->refineSystemPrompts($tenantId);
            $improvements['error_fixes'] = $this->analyzeAndFixErrors($tenantId);

            $improvements['total'] = array_sum(array_slice($improvements, 0, -1));

            return [
                'status' => 'completed',
                'improvements' => $improvements,
                'improved_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    protected function improveIntentClassification(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::IMPROVEMENT_LOG_TABLE)) {
                return 0;
            }

            // Get recent low-confidence interactions
            $lowConfidenceInteractions = DB::table(self::IMPROVEMENT_LOG_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('confidence', '<', 0.7)
                ->whereNull('feedback_score')
                ->limit(self::ANALYSIS_BATCH_SIZE)
                ->orderByDesc('created_at')
                ->get();

            if ($lowConfidenceInteractions->isEmpty()) {
                return 0;
            }

            // Use cloud AI to analyze intent patterns
            $analysis = $this->analyzeIntentPatternsWithCloud($lowConfidenceInteractions);

            $improved = 0;

            // Apply improvements to matching future interactions
            foreach ($analysis['recommendations'] ?? [] as $recommendation) {
                // Update improvement logs with cloud feedback
                $updated = DB::table(self::IMPROVEMENT_LOG_TABLE)
                    ->where('tenant_id', $tenantId)
                    ->where('action', $recommendation['corrected_action'])
                    ->whereNull('feedback_score')
                    ->update([
                        'feedback_score' => $recommendation['confidence'] ?? 0.85,
                        'improvement_suggestion' => json_encode($recommendation),
                        'updated_at' => now(),
                    ]);

                $improved += $updated;
            }

            return min($improved, $lowConfidenceInteractions->count());
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function analyzeIntentPatternsWithCloud($interactions): array
    {
        try {
            $interactionSummary = $interactions->map(function ($interaction) {
                return [
                    'message' => $interaction->input_message,
                    'detected_action' => $interaction->action,
                    'confidence' => $interaction->confidence,
                ];
            })->values()->toArray();

            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model', 'claude-3-5-sonnet-20241022'),
                'max_tokens' => 2048,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildIntentAnalysisPrompt($interactionSummary),
                    ],
                ],
            ]);

            if (!$response->successful()) {
                return ['recommendations' => []];
            }

            return $this->parseCloudAnalysisResponse($response->json());
        } catch (\Exception $e) {
            return ['recommendations' => []];
        }
    }

    protected function buildIntentAnalysisPrompt(array $interactions): string
    {
        $json = json_encode($interactions, JSON_PRETTY_PRINT);

        return <<<PROMPT
Analyze these low-confidence interactions from a business assistant system and recommend intent classification improvements:

$json

For each interaction, identify:
1. What the true intent should be
2. Key phrases that indicate this intent
3. Confidence level for the recommendation

Return a JSON array of recommendations with corrected_action and confidence fields.
Only include high-confidence recommendations (>0.85).
PROMPT;
    }

    protected function optimizeMemoryPatterns(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::IMPROVEMENT_LOG_TABLE)) {
                return 0;
            }

            // Get frequently accessed but low-relevance memories
            $patterns = DB::table(self::IMPROVEMENT_LOG_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('feedback_score', '<', 0.5)
                ->groupBy('action')
                ->selectRaw('action, COUNT(*) as frequency')
                ->having('frequency', '>=', 5)
                ->limit(20)
                ->get();

            $optimized = 0;

            foreach ($patterns as $pattern) {
                // Use cloud AI to suggest optimization
                $suggestion = $this->getCloudOptimizationSuggestion($pattern->action);

                if ($suggestion && $suggestion['should_optimize']) {
                    // Mark for memory reorganization
                    DB::table('local_intelligence_memories')
                        ->where('tenant_id', $tenantId)
                        ->where('action', $pattern->action)
                        ->update([
                            'optimization_hint' => json_encode($suggestion),
                            'updated_at' => now(),
                        ]);

                    $optimized++;
                }
            }

            return $optimized;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getCloudOptimizationSuggestion(string $action): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 500,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Should we optimize memory for the action '{$action}'? Consider it if it appears in low-relevance interactions. Respond with JSON: {should_optimize: bool, reason: string}",
                    ],
                ],
            ]);

            if ($response->successful()) {
                $text = $response->json()['content'][0]['text'] ?? '{}';
                return json_decode($text, true);
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return null;
    }

    protected function refineSystemPrompts(string $tenantId): int
    {
        try {
            // Analyze error patterns
            $errors = DB::table(self::IMPROVEMENT_LOG_TABLE)
                ->where('tenant_id', $tenantId)
                ->whereNotNull('error_message')
                ->limit(50)
                ->get();

            if ($errors->isEmpty()) {
                return 0;
            }

            // Get cloud suggestions for prompt refinement
            $refinements = $this->getPromptRefinementSuggestions($errors);

            if (!empty($refinements)) {
                // Store refinement suggestions for manual review
                DB::table('prompt_refinement_suggestions')->insert([
                    'tenant_id' => $tenantId,
                    'suggestions' => json_encode($refinements),
                    'created_at' => now(),
                ]);

                return count($refinements);
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return 0;
    }

    protected function getPromptRefinementSuggestions($errors): array
    {
        try {
            $errorSummary = $errors->pluck('error_message')->unique()->implode("\n");

            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "These are common errors from a business assistant:\n$errorSummary\n\nSuggest 3-5 system prompt refinements to prevent these. Return as JSON array of strings.",
                    ],
                ],
            ]);

            if ($response->successful()) {
                $text = $response->json()['content'][0]['text'] ?? '[]';
                return json_decode($text, true) ?? [];
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return [];
    }

    protected function analyzeAndFixErrors(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::IMPROVEMENT_LOG_TABLE)) {
                return 0;
            }

            $recentErrors = DB::table(self::IMPROVEMENT_LOG_TABLE)
                ->where('tenant_id', $tenantId)
                ->whereNotNull('error_message')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            $fixed = 0;

            foreach ($recentErrors as $error) {
                // Use LocalBrain to verify error pattern
                $analysis = $this->localBrain->process(
                    input: "Analyze error: {$error->error_message}",
                    context: [
                        'error_type' => 'interaction',
                        'assessment' => 'fixable',
                    ]
                );

                if (($analysis['confidence'] ?? 0) >= 0.7) {
                    // Mark as fixable and analyzed
                    DB::table(self::IMPROVEMENT_LOG_TABLE)
                        ->where('id', $error->id)
                        ->update([
                            'error_analyzed' => true,
                            'analysis_result' => json_encode($analysis),
                            'updated_at' => now(),
                        ]);

                    $fixed++;
                }
            }

            return $fixed;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function parseCloudAnalysisResponse(array $response): array
    {
        $content = $response['content'][0]['text'] ?? '';

        try {
            // Extract JSON from response
            if (preg_match('/\[.*\]/s', $content, $matches)) {
                $parsed = json_decode($matches[0], true);
                return ['recommendations' => $parsed ?? []];
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return ['recommendations' => []];
    }

    public function getImprovementStats(string $tenantId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::IMPROVEMENT_LOG_TABLE)) {
                return [];
            }

            $total = DB::table(self::IMPROVEMENT_LOG_TABLE)
                ->where('tenant_id', $tenantId)
                ->count();

            $improved = DB::table(self::IMPROVEMENT_LOG_TABLE)
                ->where('tenant_id', $tenantId)
                ->whereNotNull('feedback_score')
                ->where('feedback_score', '>=', 0.8)
                ->count();

            $analyzed = DB::table(self::IMPROVEMENT_LOG_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('error_analyzed', true)
                ->count();

            return [
                'total_interactions_logged' => $total,
                'interactions_improved' => $improved,
                'improvement_rate' => $total > 0 ? round(($improved / $total) * 100, 2) : 0,
                'errors_analyzed' => $analyzed,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
