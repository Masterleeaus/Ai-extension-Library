<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Skills;

final readonly class SkillDefinition
{
    public function __construct(
        public string $name,
        public array $tools,
        public array $memoryScopes,
        public array $personas,
        public string $maximumRisk,
        public array $approvalGates,
        public array $successCriteria,
        public array $metrics,
    ) {}
}
