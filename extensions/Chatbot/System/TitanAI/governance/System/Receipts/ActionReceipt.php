<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Receipts;

final readonly class ActionReceipt
{
    public function __construct(
        public string $receiptId,
        public string $action,
        public string $status,
        public string $riskLevel,
        public array $council,
        public ?string $approvalId,
        public ?array $workCoreResult,
        public ?array $rollback,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
