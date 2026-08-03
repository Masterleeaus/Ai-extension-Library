<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class CustomerServiceAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'customer-service-assistant'; }
    public static function name(): string { return 'Customer Service Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Resolve enquiries',
            'Coordinate customer updates',
            'Prepare complaint and retention actions'
        ];
    }

    public static function permissions(): array
    {
        return [
            'customers.read',
            'communications.read',
            'jobs.read'
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
