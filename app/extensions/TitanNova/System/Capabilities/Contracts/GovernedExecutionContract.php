<?php

declare(strict_types=1);

namespace App\Extensions\TitanNova\System\Capabilities\Contracts;

use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;

interface GovernedExecutionContract
{
    /** @param array<string,mixed> $payload @param array<string,mixed> $context @return array<string,mixed> */
    public function execute(GovernedToolDefinition $definition, array $payload, array $context): array;
}
