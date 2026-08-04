<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Imports;

final readonly class SkillStagedImport
{
    public function __construct(
        public string $id,
        public string $path,
    ) {
    }
}
