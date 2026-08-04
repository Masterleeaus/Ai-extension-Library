<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class QuotingAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'quoting-assistant'; }
    public static function name(): string { return 'Quoting Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Collect scope and service details',
            'Apply cleaning pricing policies',
            'Prepare quotes for approval and issue'
        ];
    }

    public static function permissions(): array
    {
        return [
            'customers.read',
            'properties.read',
            'quotes.read',
            'pricing.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
