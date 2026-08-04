<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class PricingOptimizationAgent extends AbstractAIWorker
{
    public static function id(): string { return 'pricing-optimization-agent'; }
    public static function name(): string { return 'Pricing Optimization Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return [
            'Analyse service cost and margin',
            'Recommend governed price adjustments',
            'Compare demand capacity and market context',
    ]; }
    public static function permissions(): array { return [
            'pricing.read',
            'pricing.optimize',
            'financial.read',
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
