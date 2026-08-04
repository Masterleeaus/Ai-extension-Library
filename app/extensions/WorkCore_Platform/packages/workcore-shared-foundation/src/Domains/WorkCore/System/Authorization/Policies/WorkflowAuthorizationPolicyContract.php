<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Authorization\Policies;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;

interface WorkflowAuthorizationPolicyContract
{
    public function canExecuteWorkflow(
        string $workflowName,
        OperationContextContract $context,
        array $workflowData = [],
    ): bool;

    public function canExecuteAction(
        string $actionName,
        OperationContextContract $context,
        array $actionData = [],
    ): bool;

    public function getDenialReasonForWorkflow(
        string $workflowName,
        string $actionName,
        OperationContextContract $context,
    ): ?string;
}
