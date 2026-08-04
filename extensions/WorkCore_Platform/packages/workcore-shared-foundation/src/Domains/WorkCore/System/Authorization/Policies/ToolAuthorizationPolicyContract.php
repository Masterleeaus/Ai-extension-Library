<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Authorization\Policies;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;

interface ToolAuthorizationPolicyContract
{
    public function canExecuteTool(
        string $toolName,
        OperationContextContract $context,
        array $parameters = [],
    ): bool;

    public function getDenialReason(
        string $toolName,
        OperationContextContract $context,
    ): ?string;
}
