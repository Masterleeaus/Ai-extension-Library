<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Tools;

final readonly class GovernedToolDefinition
{
    public function __construct(
        public string $name,
        public string $domain,
        public string $operation,
        public string $riskLevel,
        public array $permissions = [],
        public bool $audited = true,
        public bool $idempotent = true,
        public bool $rollbackSupported = false,
        public int $timeoutSeconds = 30,
    ) {}
}
