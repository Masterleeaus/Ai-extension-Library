<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

final class SkillToolInstructionCompiler
{
    /** @param list<string> $capabilities */
    public function compile(string $instructions, SkillToolSet $tools, array $capabilities = []): string
    {
        if ($tools->isEmpty() && $capabilities === []) {
            return $instructions;
        }

        $sections = [rtrim($instructions)];

        if ($capabilities !== []) {
            $sections[] = "Required capabilities:\n- " . implode("\n- ", $capabilities);
        }

        if (! $tools->isEmpty()) {
            $lines = [];
            foreach ($tools->definitions() as $tool) {
                $flags = [];
                if ($tools->requires($tool->key)) {
                    $flags[] = 'required';
                }
                if ($tool->approval !== SkillToolApproval::NEVER) {
                    $flags[] = 'approval: ' . $tool->approval;
                }
                $suffix = $flags === [] ? '' : ' [' . implode(', ', $flags) . ']';
                $lines[] = '- ' . $tool->key . ' → ' . $tool->functionName . $suffix . ': ' . $tool->description;
            }

            $sections[] = "Tool policy:\nUse only the tools listed below for this skill. "
                . "Do not substitute an undeclared write action. Respect approval requirements before execution.\n"
                . implode("\n", $lines);
        }

        return implode("\n\n---\n\n", $sections);
    }
}
