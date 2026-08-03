<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Approvals;

interface ApprovalQueue
{
    public function enqueue(array $request): string;

    public function resolve(string $approvalId, string $decision, string $actorId, ?string $note = null): void;

    public function find(string $approvalId): ?array;
}
