<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class UpdateCustomerAgent extends AbstractAIWorker
{
    public static function id(): string { return 'update-customer-agent'; }
    public static function name(): string { return 'Update Customer Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }

    public static function capabilities(): array
    {
        return [
            'Apply one approved customer update'
        ];
    }

    public static function permissions(): array
    {
        return [
            'customers.read',
            'customers.update'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_4_tools',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
