<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class CustomerManager extends AbstractAIWorker
{
    public static function id(): string { return 'customer-manager'; }
    public static function name(): string { return 'Customer Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Customer relationships',
            'Preferences and communication oversight',
            'Complaints, retention and follow-up'
        ];
    }

    public static function permissions(): array
    {
        return [
            'customers.read',
            'contacts.read',
            'communications.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_2_assistants',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
