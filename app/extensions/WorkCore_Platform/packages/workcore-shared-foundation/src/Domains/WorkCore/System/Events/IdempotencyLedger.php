<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Events;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;

final class IdempotencyLedger implements IdempotencyLedgerContract
{
    private string $tableName = 'workcore_idempotency_ledger';

    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function hasProcessed(string $idempotencyKey, int $tenantId): bool
    {
        return $this->connection
            ->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();
    }

    public function markProcessed(
        string $idempotencyKey,
        int $tenantId,
        array $result = [],
    ): void {
        $this->connection
            ->table($this->tableName)
            ->upsert(
                [
                    [
                        'tenant_id' => $tenantId,
                        'idempotency_key' => $idempotencyKey,
                        'result' => json_encode($result),
                        'processed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ],
                ['tenant_id', 'idempotency_key'],
                ['result', 'updated_at']
            );
    }

    public function getResult(string $idempotencyKey, int $tenantId): ?array
    {
        $record = $this->connection
            ->table($this->tableName)
            ->where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if (!$record || !$record->result) {
            return null;
        }

        return json_decode($record->result, true);
    }

    public function cleanup(DateTimeImmutable $olderThan): int
    {
        return $this->connection
            ->table($this->tableName)
            ->where('processed_at', '<', $olderThan->format('Y-m-d H:i:s'))
            ->delete();
    }

    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }
}
