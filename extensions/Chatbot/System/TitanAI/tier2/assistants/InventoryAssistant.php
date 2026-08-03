<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class InventoryAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'inventory-assistant'; }
    public static function name(): string { return 'Inventory Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Monitor stock and consumables',
            'Prepare replenishment actions',
            'Track equipment allocation'
        ];
    }

    public static function permissions(): array
    {
        return [
            'inventory.read',
            'equipment.read',
            'suppliers.read'
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
