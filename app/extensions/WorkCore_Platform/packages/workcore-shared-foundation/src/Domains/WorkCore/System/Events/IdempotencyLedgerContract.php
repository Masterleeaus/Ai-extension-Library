<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Events;

interface IdempotencyLedgerContract
{
    public function hasProcessed(string $idempotencyKey, int $tenantId): bool;

    public function markProcessed(
        string $idempotencyKey,
        int $tenantId,
        array $result = [],
    ): void;

    public function getResult(string $idempotencyKey, int $tenantId): ?array;

    public function cleanup(\DateTimeImmutable $olderThan): int;
}
