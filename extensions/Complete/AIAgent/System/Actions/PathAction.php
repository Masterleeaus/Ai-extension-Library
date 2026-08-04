<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Actions;

use App\Extensions\AIAgent\System\Actions\Contracts\AIAgentActionInterface;
use App\Extensions\AIAgent\System\Engine\ActionDispatcher;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflow;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun;

class PathAction implements AIAgentActionInterface
{
    public function __construct(
        private readonly ActionDispatcher $dispatcher,
    ) {}

    /**
     * Config keys:
     *   - paths (array): Array of path branches, each with:
     *     - id (string)          : Unique branch identifier
     *     - name (string)        : Display name (e.g. "Path A")
     *     - ruleGroups (array)   : OR-combined groups of AND-combined rules
     *                              Empty array = fallback (always executes if no prior path matched)
     *     - steps (array)        : Sub-steps to execute when this branch matches
     */
    public function execute(array $config, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array
    {
        $paths = $config['paths'] ?? [];
        $anyMatched = false;

        foreach ($paths as $path) {
            $isFallback = empty($path['ruleGroups']);

            if ($isFallback && $anyMatched) {
                continue;
            }

            $matches = $isFallback || $this->evaluatePath($path, $context);

            if (! $matches) {
                continue;
            }

            $anyMatched = true;
            $context = $this->executeSubSteps($path['steps'] ?? [], $context, $workflow, $run);
        }

        return $context;
    }

    private function evaluatePath(array $path, array $context): bool
    {
        foreach ($path['ruleGroups'] as $group) {
            if ($this->evaluateGroup($group, $context)) {
                return true;
            }
        }

        return false;
    }

    private function evaluateGroup(array $group, array $context): bool
    {
        foreach ($group['rules'] ?? [] as $rule) {
            if (! $this->evaluateRule($rule, $context)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateRule(array $rule, array $context): bool
    {
        $field = $this->interpolate($rule['field'] ?? '', $context);
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? '';

        return match ($operator) {
            'contains'      => str_contains($field, $value),
            'not_contains'  => ! str_contains($field, $value),
            'equals'        => $field === $value,
            'not_equals'    => $field !== $value,
            'is_empty'      => trim($field) === '',
            'is_not_empty'  => trim($field) !== '',
            'starts_with'   => str_starts_with($field, $value),
            'ends_with'     => str_ends_with($field, $value),
            'greater_than'  => is_numeric($field) && is_numeric($value) && (float) $field > (float) $value,
            'less_than'     => is_numeric($field) && is_numeric($value) && (float) $field < (float) $value,
            default         => false,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function executeSubSteps(array $steps, array $context, AIAgentWorkflow $workflow, AIAgentWorkflowRun $run): array
    {
        foreach ($steps as $stepDef) {
            $type = $stepDef['type'] ?? $stepDef['action'] ?? '';
            $config = $stepDef['config'] ?? [];
            $action = $this->dispatcher->resolve($type);
            $context = $action->execute($config, $context, $workflow, $run);
        }

        return $context;
    }

    private function interpolate(string $template, array $context): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function (array $matches) use ($context): string {
            $value = $context[$matches[1]] ?? $matches[0];

            if (is_array($value)) {
                return json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) ?: $matches[0];
            }

            return (string) $value;
        }, $template);
    }

    public function getCategory(): string
    {
        return 'flow_controls';
    }

    public function getLabel(): string
    {
        return 'Path';
    }

    public function getDescription(): string
    {
        return 'Split execution into multiple branches based on conditions.';
    }

    public function getIcon(): string
    {
        return 'tabler-git-fork';
    }

    public function getConfigSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'paths' => [
                    'type'        => 'array',
                    'description' => 'Array of conditional path branches.',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'         => ['type' => 'string'],
                            'name'       => ['type' => 'string'],
                            'ruleGroups' => ['type' => 'array'],
                            'steps'      => ['type' => 'array'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
