<?php

declare(strict_types=1);

namespace App\Extensions\TitanNova\System\Capabilities;

use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolExecutor;
use App\Extensions\TitanNova\System\Capabilities\Contracts\GovernedExecutionContract;

final class TitanAIGovernedExecutionAdapter implements GovernedExecutionContract
{
    public function __construct(private GovernedToolExecutor $executor) {}

    public function execute(GovernedToolDefinition $definition, array $payload, array $context): array
    {
        return $this->executor->execute($definition, $payload, $context);
    }
}
