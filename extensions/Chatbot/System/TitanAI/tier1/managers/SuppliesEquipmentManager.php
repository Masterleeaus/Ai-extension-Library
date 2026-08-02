<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class SuppliesEquipmentManager extends AbstractAIWorker
{
    public static function id(): string { return 'supplies-equipment-manager'; }
    public static function name(): string { return 'Supplies and Equipment Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Consumables and stock oversight',
            'Equipment maintenance',
            'Supplier coordination'
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
            'delegates_to' => 'tier_2_assistants',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
