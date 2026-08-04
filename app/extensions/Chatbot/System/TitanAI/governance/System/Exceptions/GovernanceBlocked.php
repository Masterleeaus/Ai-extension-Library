<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Exceptions;

use RuntimeException;

final class GovernanceBlocked extends RuntimeException
{
    public function __construct(
        public readonly string $decision,
        public readonly ?string $approvalId = null,
        string $message = 'Governed action blocked.',
    ) {
        parent::__construct($message);
    }
}
