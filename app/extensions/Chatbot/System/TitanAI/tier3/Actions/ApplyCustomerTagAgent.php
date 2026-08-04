<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class ApplyCustomerTagAgent extends AbstractAIWorker
{
    public static function id(): string { return 'apply-customer-tag-agent'; }
    public static function name(): string { return 'Apply Customer Tag Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }

    public static function capabilities(): array
    {
        return [
            'Apply one approved tag to a customer'
        ];
    }

    public static function permissions(): array
    {
        return [
            'customers.read',
            'customer_tags.apply'
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
