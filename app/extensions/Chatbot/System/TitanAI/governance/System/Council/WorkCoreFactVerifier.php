<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Council;

use App\Extensions\TitanAIGovernance\System\Contracts\WorkCoreGateway;

final class WorkCoreFactVerifier
{
    public function __construct(private WorkCoreGateway $workCore) {}

    public function verify(string $action, array $payload): array
    {
        return $this->workCore->read('governance', 'verify_action', [
            'action' => $action,
            'payload' => $payload,
            'checks' => ['record_exists', 'permissions', 'availability', 'financial_totals', 'version'],
        ]);
    }
}
