<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use RuntimeException;

/**
 * Describes how a Tier 3 worker is executable without invoking it prematurely.
 * Declarative agents are valid only when they delegate to governed WorkCore actions.
 */
final class AgentExecutionPathResolver
{
    public function __construct(
        private readonly WorkerRouteRegistry $workers,
        private readonly WorkCoreToolMappingRegistry $toolMappings,
    ) {}

    /** @return array{agent_id:string,class:class-string,mode:string,authority:string,executable:bool,native_implementation:bool,workcore_tool:?string} */
    public function resolve(string $agentId, ?string $domain = null, ?string $operation = null): array
    {
        $canonical = $this->workers->canonicalId($agentId);
        $class = $this->workers->classFor($canonical);
        if ($class === null || $this->workers->tierFor($canonical) !== 3) {
            throw new RuntimeException("Tier 3 agent [{$agentId}] is not registered.");
        }

        $definition = $this->workers->definitionFor($canonical) ?? [];
        $authority = (string) ($definition['operational_authority'] ?? '');
        $native = is_subclass_of($class, ExecutableAgent::class);
        $delegates = (string) ($definition['delegates_to'] ?? '');
        $governed = $authority === 'workcore_api_only' || $delegates === 'tier_4_tools';

        if (! $native && ! $governed) {
            throw new RuntimeException("Tier 3 agent [{$canonical}] has no executable or governed WorkCore path.");
        }

        $workcoreTool = null;
        if ($domain !== null || $operation !== null) {
            if ($domain === null || $operation === null) {
                throw new RuntimeException('Both domain and operation are required when validating a WorkCore execution path.');
            }
            $workcoreTool = $this->toolMappings->toolFor($domain, $operation);
        }

        return [
            'agent_id' => $canonical,
            'class' => $class,
            'mode' => 'governed_workcore',
            'authority' => $authority === '' ? 'workcore_api_only' : $authority,
            'executable' => true,
            'native_implementation' => $native,
            'workcore_tool' => $workcoreTool,
        ];
    }

    /** @return array{valid:int,invalid:array<string,string>,modes:array<string,int>} */
    public function diagnostics(): array
    {
        $valid = 0;
        $invalid = [];
        $modes = ['governed_workcore' => 0, 'native_implementations' => 0];
        foreach ($this->workers->all() as $id => $class) {
            if ($this->workers->tierFor($id) !== 3) continue;
            try {
                $path = $this->resolve($id);
                $valid++;
                $modes[$path['mode']] = ($modes[$path['mode']] ?? 0) + 1;
                if ($path['native_implementation']) $modes['native_implementations']++;
            } catch (RuntimeException $exception) {
                $invalid[$id] = $exception->getMessage();
            }
        }
        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'modes' => $modes,
            'tool_mappings' => $this->toolMappings->diagnostics(),
        ];
    }
}
