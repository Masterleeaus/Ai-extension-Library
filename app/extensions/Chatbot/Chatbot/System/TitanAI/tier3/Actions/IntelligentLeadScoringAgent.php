<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class IntelligentLeadScoringAgent extends AbstractAIWorker
{
    public static function id(): string { return 'intelligent-lead-scoring-agent'; }
    public static function name(): string { return 'Intelligent Lead Scoring Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return [
            'Score lead conversion likelihood',
            'Identify service need and urgency',
            'Recommend follow-up priority',
    ]; }
    public static function permissions(): array { return [
            'leads.read',
            'leads.score',
            'predictive.read',
    ]; }
    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_4_tools',
            'operational_authority' => 'workcore_api_only',
            'execution_mode' => 'governed_delegation',
            'requires_confirmation_for_mutation' => true,
        ];
    }
}
