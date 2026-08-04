<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Authorization\Policies;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;

interface ConnectorAuthorizationPolicyContract
{
    public function canUseConnector(
        string $connectorName,
        OperationContextContract $context,
        array $connectorConfig = [],
    ): bool;

    public function canExecuteConnectorAction(
        string $connectorName,
        string $actionName,
        OperationContextContract $context,
        array $actionData = [],
    ): bool;

    public function getDenialReasonForConnector(
        string $connectorName,
        OperationContextContract $context,
    ): ?string;
}
