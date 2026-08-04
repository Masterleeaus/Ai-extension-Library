<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Personas;

final readonly class OperatingPersona
{
    public function __construct(
        public string $name,
        public string $tone,
        public string $reasoningDepth,
        public string $riskTolerance,
        public array $toolPermissions,
        public array $allowedSkills,
        public array $preferredModels,
        public array $memoryScopes,
        public array $approvalRules,
        public array $outputRules,
    ) {}

    public function allowsTool(string $tool): bool
    {
        return in_array($tool, $this->toolPermissions, true);
    }
}
