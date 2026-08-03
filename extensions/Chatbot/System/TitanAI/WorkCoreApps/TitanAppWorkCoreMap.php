<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\WorkCoreApps;

use InvalidArgumentException;

final class TitanAppWorkCoreMap
{
    public function __construct(private readonly WorkCoreAppBridge $bridge) {}

    /** @return array<string,array<string,mixed>> */
    public function all(): array { return $this->bridge->apps(); }

    /** @return array<string,mixed> */
    public function definition(string $app): array
    {
        return $this->bridge->app($app) ?? throw new InvalidArgumentException("Unknown Titan app [{$app}].");
    }

    /** @return list<string> */
    public function tools(string $app, string $kind = 'all'): array
    {
        $workcore = (array) ($this->definition($app)['workcore'] ?? []);
        $reads = array_values((array) ($workcore['read_tools'] ?? []));
        $actions = array_values((array) ($workcore['action_tools'] ?? []));
        return match ($kind) { 'read' => $reads, 'action' => $actions, default => array_values(array_unique([...$reads, ...$actions])) };
    }

    public function allows(string $app, string $tool): bool
    {
        return in_array($tool, $this->tools($app), true);
    }
}
