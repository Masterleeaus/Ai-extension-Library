<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

final readonly class SkillToolExecutionContext
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public ?int $userId = null,
        public ?int $companyId = null,
        public bool $approved = false,
        public array $metadata = [],
    ) {}
}
