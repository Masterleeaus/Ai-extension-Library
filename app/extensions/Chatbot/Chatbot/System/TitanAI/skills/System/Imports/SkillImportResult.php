<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Imports;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;

final readonly class SkillImportResult
{
    /** @param array<string, mixed> $report */
    public function __construct(
        public Skill $skill,
        public array $report,
    ) {
    }
}
