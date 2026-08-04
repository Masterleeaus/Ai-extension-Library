<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

use Throwable;

final readonly class SkillToolExecutionService
{
    public function __construct(private SkillToolRegistry $registry) {}

    /** @param array<string, mixed> $arguments */
    public function execute(
        SkillToolSet $tools,
        string $toolKey,
        array $arguments,
        SkillToolExecutionContext $context,
    ): SkillToolExecutionResult {
        if (! $tools->allows($toolKey)) {
            return SkillToolExecutionResult::denied("Skill tool [{$toolKey}] is not allowed by this skill version.");
        }

        $declared = $tools->definition($toolKey);
        $registered = $this->registry->definition($toolKey);

        if ($declared === null || $registered === null || ! $this->registry->has($toolKey)) {
            return SkillToolExecutionResult::unavailable("Skill tool [{$toolKey}] is not available in the current runtime.");
        }

        $definition = SkillToolDefinitionMerger::merge($declared, $registered);
        $approval = $definition->approval;

        if ($approval === SkillToolApproval::REQUIRED && ! $context->approved) {
            return SkillToolExecutionResult::approval("Skill tool [{$toolKey}] requires explicit approval.");
        }

        $missing = $this->missingRequiredArguments($definition, $arguments);
        if ($missing !== []) {
            return SkillToolExecutionResult::failed('Missing required tool arguments: ' . implode(', ', $missing) . '.');
        }

        if (! $this->registry->canExecute($toolKey)) {
            return SkillToolExecutionResult::unavailable("Skill tool [{$toolKey}] is declared but has no executable adapter.");
        }

        try {
            $result = $this->registry->execute($toolKey, $arguments, $context);
        } catch (Throwable $exception) {
            return SkillToolExecutionResult::failed($exception->getMessage());
        }

        return SkillToolExecutionResult::success(is_array($result) ? $result : ['result' => $result]);
    }

    /** @param array<string, mixed> $arguments @return list<string> */
    private function missingRequiredArguments(SkillToolDefinition $definition, array $arguments): array
    {
        $required = $definition->inputSchema['required'] ?? [];
        if (! is_array($required)) {
            return [];
        }

        return array_values(array_filter($required, static fn (mixed $key): bool =>
            is_string($key) && (! array_key_exists($key, $arguments) || $arguments[$key] === null)
        ));
    }
}
