<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Diagnostics;

use App\Services\AI\Integration\WorkCore\Contracts\HostWorkCoreActionBus;
use App\Services\AI\Integration\WorkCore\Contracts\HostWorkCoreReadBus;
use Illuminate\Contracts\Container\Container;

final class WorkCoreBindingDiagnostics
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string,mixed> */
    public function report(): array
    {
        [$appReads, $appActions, $appCount] = $this->titanAppTools();
        $actions = array_values(array_unique([
            ...array_keys((array) config('titan_ai_runtime.cleaning_actions', [])),
            ...$appActions,
        ]));
        $reads = $appReads;

        $actionHandlers = (array) config('titan_ai_runtime.host_action_handlers', []);
        $actionBusTarget = config('titan_ai_runtime.host_action_bus');
        $actionContractBound = $this->container->bound(HostWorkCoreActionBus::class);

        $readHandlers = (array) config('titan_ai_runtime.host_read_handlers', []);
        $readBusTarget = config('titan_ai_runtime.host_read_bus');
        $readContractBound = $this->container->bound(HostWorkCoreReadBus::class);

        [$resolvedActions, $missingActions] = $this->resolve(
            $actions,
            $actionHandlers,
            $actionContractBound,
            HostWorkCoreActionBus::class,
            $actionBusTarget,
        );
        [$resolvedReads, $missingReads] = $this->resolve(
            $reads,
            $readHandlers,
            $readContractBound,
            HostWorkCoreReadBus::class,
            $readBusTarget,
        );

        return [
            'ready' => $missingActions === [] && $missingReads === [],
            'titan_app_count' => $appCount,
            'action_count' => count($actions),
            'read_count' => count($reads),
            'resolved_count' => count($resolvedActions) + count($resolvedReads),
            'missing_count' => count($missingActions) + count($missingReads),
            'resolved' => ['actions' => $resolvedActions, 'reads' => $resolvedReads],
            'missing' => ['actions' => $missingActions, 'reads' => $missingReads],
            'action_contract_bound' => $actionContractBound,
            'read_contract_bound' => $readContractBound,
            'action_bus_target' => $actionBusTarget,
            'read_bus_target' => $readBusTarget,
        ];
    }

    /**
     * @param list<string> $keys
     * @param array<string,mixed> $handlers
     * @return array{0:array<string,array<string,string>>,1:list<string>}
     */
    private function resolve(
        array $keys,
        array $handlers,
        bool $contractBound,
        string $contract,
        mixed $busTarget,
    ): array {
        $resolved = [];
        $missing = [];
        foreach ($keys as $key) {
            $target = $handlers[$key] ?? null;
            if (is_string($target) && trim($target) !== '') {
                $resolved[$key] = ['mode' => 'handler', 'target' => $target];
            } elseif ($contractBound) {
                $resolved[$key] = ['mode' => 'contract', 'target' => $contract];
            } elseif (is_string($busTarget) && trim($busTarget) !== '') {
                $resolved[$key] = ['mode' => 'bus', 'target' => $busTarget];
            } else {
                $missing[] = $key;
            }
        }
        return [$resolved, $missing];
    }

    /** @return array{0:list<string>,1:list<string>,2:int} */
    private function titanAppTools(): array
    {
        $directory = dirname(__DIR__, 3).'/WorkCoreApps/config';
        $reads = [];
        $actions = [];
        $count = 0;
        foreach (glob($directory.'/*-workcore.json') ?: [] as $file) {
            $manifest = json_decode((string) file_get_contents($file), true);
            if (! is_array($manifest) || ! ($manifest['workcore_enabled'] ?? false)) continue;
            $count++;
            foreach ((array) ($manifest['workcore']['read_tools'] ?? []) as $tool) $reads[(string) $tool] = true;
            foreach ((array) ($manifest['workcore']['action_tools'] ?? []) as $tool) $actions[(string) $tool] = true;
        }
        return [array_keys($reads), array_keys($actions), $count];
    }
}
