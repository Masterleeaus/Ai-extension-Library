<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Contracts;

use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;

interface ExecutableAgent
{
    public function execute(object $input, WorkerExecutionContext $context): object;
}
