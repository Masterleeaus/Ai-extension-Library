<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

use App\Extensions\SystemAIChatSkills\System\Services\SkillToolService;
use Illuminate\Support\Collection;

final class SkillToolHostAdapter
{
    /**
     * Execute an SystemAIChat skill tool wrapper through the governed skill runtime.
     *
     * @param array<string, mixed> $arguments
     * @param Collection<int, \App\Extensions\SystemAIChatSkills\System\Models\Skill> $skills
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    public function executeFunction(
        string $functionName,
        array $arguments,
        Collection $skills,
        ?int $userId = null,
        ?int $companyId = null,
        bool $approved = false,
        array $metadata = [],
    ): array {
        $result = SkillToolService::executeToolFunction(
            $functionName,
            $arguments,
            $skills,
            new SkillToolExecutionContext(
                userId: $userId,
                companyId: $companyId,
                approved: $approved,
                metadata: $metadata,
            ),
        );

        return $result->toArray() + [
            'function_name' => $functionName,
            'skill' => SkillToolService::getSkillMeta($functionName, $skills),
            'approval_required' => $result->approvalRequired,
        ];
    }
}
