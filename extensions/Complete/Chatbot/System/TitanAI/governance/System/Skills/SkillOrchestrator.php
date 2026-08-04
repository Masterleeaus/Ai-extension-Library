<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Skills;

use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolExecutor;
use RuntimeException;

final class SkillOrchestrator
{
    public function __construct(private GovernedToolExecutor $executor) {}

    public function run(SkillDefinition $skill, array $steps, array $context): array
    {
        $results = [];
        foreach ($steps as $index => $step) {
            if (! in_array($step['tool'] ?? null, $skill->tools, true)) {
                throw new RuntimeException("Tool is not allowed by skill at step {$index}");
            }
            $definition = new GovernedToolDefinition(
                (string) $step['tool'],
                (string) $step['domain'],
                (string) $step['operation'],
                (string) ($step['risk_level'] ?? $skill->maximumRisk),
                (array) ($step['permissions'] ?? []),
                true,
                true,
                (bool) ($step['rollback_supported'] ?? false),
            );
            $results[] = $this->executor->execute($definition, (array) ($step['payload'] ?? []), $context);
        }
        return ['skill' => $skill->name, 'status' => 'completed', 'results' => $results];
    }
}
