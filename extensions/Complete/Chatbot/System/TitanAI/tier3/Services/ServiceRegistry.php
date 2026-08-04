<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Services;

use App\Services\AI\Tier3\Agents\Actions\{BookJobAgent,AssignCleanerAgent,CreateQuoteAgent};

final class ServiceRegistry
{
    /** @var array<string,class-string> */
    private const AGENTS = [
        'BookJobAgent'=>BookJobAgent::class,
        'AssignCleanerAgent'=>AssignCleanerAgent::class,
        'CreateQuoteAgent'=>CreateQuoteAgent::class,
    ];
    /** @return class-string|null */
    public static function getAgentClass(string $name): ?string { return self::AGENTS[$name] ?? null; }
    /** @return list<string> */
    public static function getAllAgents(): array { return array_keys(self::AGENTS); }
    public static function hasAgent(string $name): bool { return isset(self::AGENTS[$name]); }
}
