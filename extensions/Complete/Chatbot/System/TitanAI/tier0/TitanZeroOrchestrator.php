<?php

declare(strict_types=1);

namespace App\Services\AI\Tier0;

/**
 * Top-level runtime identity and delegation policy for Titan Zero.
 *
 * Titan Uno = Tier 1 managers
 * Titan Duo = Tier 2 assistants and specialists
 * Titan Trio = Tier 3 executable action agents
 * Titan Quattro = governed tool catalogue
 */
final class TitanZeroOrchestrator
{
    public static function id(): string { return 'titan-zero'; }
    public static function name(): string { return 'Titan Zero'; }
    public static function tier(): int { return 0; }
    public static function role(): string { return 'orchestrator'; }

    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return [
            'id' => self::id(),
            'name' => self::name(),
            'tier' => self::tier(),
            'role' => self::role(),
            'delegation' => [
                'titan_uno' => 'tier1/managers',
                'titan_duo' => 'tier2/assistants and tier2/specialists',
                'titan_trio' => 'tier3/Actions',
                'titan_quattro' => 'tools/titan-quattro-tools',
            ],
            'operational_authority' => 'workcore_api_only',
            'governance' => 'mandatory',
            'direct_mutation' => false,
        ];
    }
}
